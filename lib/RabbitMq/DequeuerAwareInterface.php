<?php

namespace Yngc0der\RabbitMq\RabbitMq;

interface DequeuerAwareInterface
{
    public function setDequeuer(DequeuerInterface $dequeuer);
}
