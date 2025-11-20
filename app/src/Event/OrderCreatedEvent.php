<?php

namespace App\Event;

use App\Entity\OrderEntity;
use Symfony\Contracts\EventDispatcher\Event;

final class OrderCreatedEvent extends Event
{
    public function __construct(private OrderEntity $order)
    {
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }
}