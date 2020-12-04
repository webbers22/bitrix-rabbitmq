<?php
/**
 * @author RG. <rg.archuser@gmail.com>
 */

namespace Yngc0der\RabbitMq\Integration\DI;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\DI\ServiceLocator;

/**
 * Class Services
 * @package Yngc0der\RabbitMq\Integration\DI
 */
class Services
{
    /** @var array */
    protected $config;

    /** @var array */
    protected $parameters;

    /** @var ServiceLocator */
    protected $container;

    public function __construct()
    {
        $this->config = Configuration::getInstance()->get('rabbitmq') ?? [];
        $this->parameters = Configuration::getInstance('yngc0der.rabbitmq')->get('parameters') ?? [];
        $this->container = ServiceLocator::getInstance();
    }

    public function load()
    {
        $this->loadConnections();
        $this->loadBindings();
        $this->loadProducers();
        $this->loadConsumers();

        $this->loadPartsHolder();
    }

    protected function loadPartsHolder()
    {
        $this->container->addInstanceLazy('rabbitmq.parts_holder', [
            'constructor' => function () {
                $className = $this->parameters['rabbitmq.parts_holder.class'];

                /** @var \Yngc0der\RabbitMq\RabbitMq\AmqpPartsHolder $instance */
                $instance = new $className();

                foreach ($this->config['bindings'] as $binding) {
                    ksort($binding);
                    $key = md5(json_encode($binding));

                    $part = $this->container->get("rabbitmq.binding.{$key}");
                    $instance->addPart('rabbitmq.binding', $part);
                }

                foreach ($this->config['producers'] as $key => $producer) {
                    $part = $this->container->get("rabbitmq.{$key}_producer");
                    $instance->addPart('rabbitmq.base_amqp', $part);
                    $instance->addPart('rabbitmq.producer', $part);
                }

                foreach ($this->config['consumers'] as $key => $consumer) {
                    $part = $this->container->get("rabbitmq.{$key}_consumer");
                    $instance->addPart('rabbitmq.base_amqp', $part);
                    $instance->addPart('rabbitmq.consumer', $part);
                }

                return $instance;
            },
        ]);
    }

    protected function loadConnections()
    {
        foreach ($this->config['connections'] as $key => $connection) {
            $connectionSuffix = $connection['use_socket'] ? 'socket_connection.class' : 'connection.class';
            $classParam = $connection['lazy']
                ? 'rabbitmq.lazy.' . $connectionSuffix
                : 'rabbitmq.' . $connectionSuffix;

            $factoryName = "rabbitmq.connection_factory.{$key}";
            $connectionName = "rabbitmq.connection.{$key}";

            $this->container->addInstanceLazy($factoryName, [
                'constructor' => function () use ($classParam, $connection) {
                    $className = $this->parameters['rabbitmq.connection_factory.class'];

                    $parametersProvider = null;

                    if (isset($connection['connection_parameters_provider'])) {
                        /** @var \Yngc0der\RabbitMQ\Provider\ConnectionParametersProviderInterface $parametersProvider */
                        $parametersProvider = $this->container->get($connection['connection_parameters_provider']);
                    }

                    /** @var \Yngc0der\RabbitMq\RabbitMq\AMQPConnectionFactory $instance */
                    $instance = new $className(
                        $this->parameters[$classParam],
                        $connection,
                        $parametersProvider
                    );

                    return $instance;
                }
            ]);
            
            $this->container->addInstanceLazy($connectionName, [
                'constructor' => function () use ($factoryName) {
                    return $this->container->get($factoryName)->createConnection();
                }
            ]);
        }
    }

    protected function loadBindings()
    {
        if ($this->config['sandbox']) {
            return;
        }

        foreach ($this->config['bindings'] as $binding) {
            ksort($binding);
            $key = md5(json_encode($binding));

            if (!isset($binding['class'])) {
                $binding['class'] = $this->parameters['rabbitmq.binding.class'];
            }

            $this->container->addInstanceLazy("rabbitmq.binding.{$key}", [
                'constructor' => function () use ($binding) {
                    $className = $binding['class'];
                    $connectionName = "rabbitmq.connection.{$binding['connection']}";

                    /** @var \Yngc0der\RabbitMq\RabbitMq\Binding $instance */
                    $instance = new $className($this->container->get($connectionName));

                    $instance->setArguments($binding['arguments']);
                    $instance->setDestination($binding['destination']);
                    $instance->setDestinationIsExchange($binding['destination_is_exchange']);
                    $instance->setExchange($binding['exchange']);
                    $instance->setNowait($binding['nowait']);
                    $instance->setRoutingKey($binding['routing_key']);

                    return $instance;
                }
            ]);
        }
    }

    protected function loadProducers()
    {
        if (!isset($this->config['sandbox']) || $this->config['sandbox'] === false) {
            foreach ($this->config['producers'] as $key => $producer) {
                $producerServiceName = "rabbitmq.{$key}_producer";

                if (!isset($producer['class'])) {
                    $producer['class'] = $this->parameters['rabbitmq.producer.class'];
                }

                // this producer doesn't define an exchange -> using AMQP Default
                if (!isset($producer['exchange_options'])) {
                    $producer['exchange_options'] = $this->getDefaultExchangeOptions();
                }

                // this producer doesn't define a queue -> using AMQP Default
                if (!isset($producer['queue_options'])) {
                    $producer['queue_options'] = $this->getDefaultQueueOptions();
                }

                $this->container->addInstanceLazy($producerServiceName, [
                    'constructor' => function () use ($producer) {
                        $className = $producer['class'];
                        $connectionName = "rabbitmq.connection.{$producer['connection']}";

                        /** @var \Yngc0der\RabbitMQ\RabbitMq\Producer $instance */
                        $instance = new $className($this->container->get($connectionName));

                        $instance->setExchangeOptions($producer['exchange_options']);
                        $instance->setQueueOptions($producer['queue_options']);

                        if (isset($producer['auto_setup_fabric']) && !$producer['auto_setup_fabric']) {
                            $instance->disableAutoSetupFabric();
                        }

                        if (isset($producer['enable_logger']) && $producer['enable_logger']) {
                            $instance->setLogger($this->container->get($producer['logger']));
                        }

                        return $instance;
                    }
                ]);
            }
        } else {
            foreach ($this->config['producers'] as $key => $producer) {
                $this->container->addInstanceLazy("rabbitmq.{$key}_producer", [
                    'className' => $this->parameters['rabbitmq.fallback.class'],
                ]);
            }
        }
    }

    protected function loadConsumers()
    {
        foreach ($this->config['consumers'] as $key => $consumer) {
            // this consumer doesn't define an exchange -> using AMQP Default
            if (!isset($consumer['exchange_options'])) {
                $consumer['exchange_options'] = $this->getDefaultExchangeOptions();
            }

            // this consumer doesn't define a queue -> using AMQP Default
            if (!isset($consumer['queue_options'])) {
                $consumer['queue_options'] = $this->getDefaultQueueOptions();
            }

            $this->container->addInstanceLazy("rabbitmq.{$key}_consumer", [
                'constructor' => function () use ($consumer) {
                    $className = $this->parameters['rabbitmq.consumer.class'];
                    $connectionName = "rabbitmq.connection.{$consumer['connection']}";

                    /** @var \Yngc0der\RabbitMQ\RabbitMq\Consumer $instance */
                    $instance = new $className($this->container->get($connectionName));

                    $instance->setExchangeOptions($consumer['exchange_options']);
                    $instance->setQueueOptions($consumer['queue_options']);

                    /** @var object $callback */
                    $callback = $this->container->get($consumer['callback']);

                    $instance->setCallback([$callback, 'execute']);

                    if (array_key_exists('qos_options', $consumer)) {
                        $instance->setQosOptions(
                            $consumer['qos_options']['prefetch_size'],
                            $consumer['qos_options']['prefetch_count'],
                            $consumer['qos_options']['global']
                        );
                    }

                    if (isset($consumer['idle_timeout'])) {
                        $instance->setIdleTimeout($consumer['idle_timeout']);
                    }

                    if (isset($consumer['idle_timeout_exit_code'])) {
                        $instance->setIdleTimeoutExitCode($consumer['idle_timeout_exit_code']);
                    }

                    if (isset($consumer['graceful_max_execution'])) {
                        $instance->setGracefulMaxExecutionDateTimeFromSecondsInTheFuture(
                            $consumer['graceful_max_execution']['timeout']
                        );
                        $instance->setGracefulMaxExecutionTimeoutExitCode(
                            $consumer['graceful_max_execution']['exit_code']
                        );
                    }

                    if (isset($consumer['auto_setup_fabric']) && !$consumer['auto_setup_fabric']) {
                        $instance->disableAutoSetupFabric();
                    }

                    if (isset($consumer['enable_logger']) && $consumer['enable_logger']) {
                        $instance->setLogger($this->container->get($consumer['logger']));
                    }

                    if ($this->isDequeverAwareInterface(get_class($callback))) {
                        /** @var \Yngc0der\RabbitMQ\RabbitMq\DequeuerAwareInterface $callback */
                        $callback->setDequeuer($instance);
                    }

                    return $instance;
                }
            ]);
        }
    }

    protected function isDequeverAwareInterface(string $class): bool
    {
        $refClass = new \ReflectionClass($class);

        return $refClass->implementsInterface('Yngc0der\RabbitMq\RabbitMq\DequeuerAwareInterface');
    }

    protected function getDefaultExchangeOptions(): array
    {
        return [
            'name' => '',
            'type' => 'direct',
            'passive' => true,
            'declare' => false,
        ];
    }

    protected function getDefaultQueueOptions(): array
    {
        return [
            'name' => '',
            'declare' => false,
        ];
    }
}
