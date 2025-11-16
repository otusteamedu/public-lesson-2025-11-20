<?php

namespace App\EventListener;

use App\Service\EventService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final readonly class KernelResponseEventListener
{
    public function __construct(private EventService $eventService)
    {
    }

    /**
     * @param ResponseEvent $event
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set('PL-App-Custom-Header', uniqid());

        $this->eventService->addBuiltInEvent(
            eventName: 'kernel.response',
            message: '',
            source: KernelResponseEventListener::class
        );
    }
}