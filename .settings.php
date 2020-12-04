<?php
/**
 * @author RG. <rg.archuser@gmail.com>
 */

return [
    'parameters' => [
        'value' => [
            'rabbitmq.connection.class' => 'PhpAmqpLib\Connection\AMQPConnection',
            'rabbitmq.socket_connection.class' => 'PhpAmqpLib\Connection\AMQPSocketConnection',
            'rabbitmq.lazy.class' => 'PhpAmqpLib\Connection\AMQPLazyConnection',
            'rabbitmq.lazy.socket_connection.class' => 'PhpAmqpLib\Connection\AMQPLazySocketConnection',
            'rabbitmq.connection_factory.class' => 'Yngc0der\RabbitMq\RabbitMq\AMQPConnectionFactory',
            'rabbitmq.binding.class' => 'Yngc0der\RabbitMq\RabbitMq\Binding',
            'rabbitmq.producer.class' => 'Yngc0der\RabbitMq\RabbitMq\Producer',
            'rabbitmq.consumer.class' => 'Yngc0der\RabbitMq\RabbitMq\Consumer',
            'rabbitmq.multi_consumer.class' => '',
            'rabbitmq.dynamic_consumer.class' => '',
            'rabbitmq.batch_consumer.class' => '',
            'rabbitmq.anon_consumer.class' => '',
            'rabbitmq.rpc_client.class' => '',
            'rabbitmq.rpc_server.class' => '',
            'rabbitmq.logged.channel.class' => '',
            'rabbitmq.parts_holder.class' => 'Yngc0der\RabbitMq\RabbitMq\AmqpPartsHolder',
            'rabbitmq.fallback.class' => 'Yngc0der\RabbitMq\RabbitMq\Fallback',
        ],
        'readonly' => false,
    ],
    'services' => [
        'value' => [
            'rabbitmq.service_loader' => [
                'className' => 'Yngc0der\RabbitMq\Integration\DI\Services',
            ],
        ],
        'readonly' => false,
    ],
];
