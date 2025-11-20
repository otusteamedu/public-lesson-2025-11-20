<?php

namespace App\EventSubscriber;

use App\Event\OrderCreatedEvent;
use App\Service\EventService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderEventsSubscriber implements EventSubscriberInterface
{
    public function __construct(private EventService $eventService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderCreatedEvent::class => ['onOrderCreated'],
        ];
    }

    /**
     * @param OrderCreatedEvent $event
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function onOrderCreated(OrderCreatedEvent $event): void
    {
        $this->eventService->addBuiltInEvent(
            eventName: 'onOrderCreated',
            message: sprintf('order [%d] created', $event->getOrder()->getId()),
            source: OrderEventsSubscriber::class
        );
    }
}