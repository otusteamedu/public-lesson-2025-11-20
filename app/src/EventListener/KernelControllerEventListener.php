<?php

namespace App\EventListener;

use App\Service\EventService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class KernelControllerEventListener
{
    public function __construct(private EventService $eventService)
    {
    }

    /**
     * @param ControllerEvent $event
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $controllerData = $event->getController();

        if (!is_array($controllerData)) {
            $message = (new \ReflectionClass($controllerData))->getShortName();
        }
        else {
            $message = implode(':', array_keys($controllerData));
        }

        $this->eventService->addBuiltInEvent(
            eventName: 'kernel.controller',
            message: $message,
            source: KernelControllerEventListener::class
        );
    }
}