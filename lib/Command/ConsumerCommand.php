<?php

namespace Yngc0der\RabbitMq\Command;

class ConsumerCommand extends BaseConsumerCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setDescription('Executes a consumer');
        $this->setName('rabbitmq:consumer');
    }

    protected function getConsumerService()
    {
        return 'rabbitmq.%s_consumer';
    }
}
