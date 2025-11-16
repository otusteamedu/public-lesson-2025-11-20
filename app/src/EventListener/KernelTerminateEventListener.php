<?php

namespace App\EventListener;

use App\Service\EventService;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

final readonly class KernelTerminateEventListener
{
    public function __construct(
        private EventService $eventService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param TerminateEvent $event
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function onKernelTerminate(TerminateEvent $event): void
    {
        $this->eventService->addBuiltInEvent(
            eventName: 'kernel.terminate',
            message: '',
            source: KernelTerminateEventListener::class
        );

        $this->logger->notice('kernel.terminate event triggered', ['eventName' => KernelTerminateEventListener::class]);
    }
}