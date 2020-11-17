<?php

namespace Yngc0der\RabbitMq\RabbitMq\Exception;


use Yngc0der\RabbitMq\RabbitMq\ConsumerInterface;

class AckStopConsumerException extends StopConsumerException
{
    public function getHandleCode()
    {
        return ConsumerInterface::MSG_ACK;
    }

}
