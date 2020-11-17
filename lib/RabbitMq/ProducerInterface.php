<?php

namespace Yngc0der\RabbitMq\RabbitMq;

interface ProducerInterface
{
    /**
     * Publish a message
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array());
}
