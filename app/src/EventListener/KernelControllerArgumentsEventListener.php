<?php

namespace App\EventListener;

use App\Service\EventService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class KernelControllerArgumentsEventListener
{
    public function __construct(
        private EventService $eventService,
        private SerializerInterface $serializer
    ) {
    }

    /**
     * @param ControllerArgumentsEvent $event
     * @return void
     *
     * @throws InvalidArgumentException
     * @throws ExceptionInterface
     */
    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $namedArguments = $event->getRequest()->attributes->all();
        $controllerArguments = $event->getArguments();

        $this->eventService->addBuiltInEvent(
            eventName: 'kernel.controller_arguments',
            message: $this->serializer->serialize($namedArguments, JsonEncoder::FORMAT),
            source: KernelControllerArgumentsEventListener::class
        );
    }
}