<?php

namespace App\EventListener;

use App\Request\RequestAttributesEnum;
use App\Service\EventService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final readonly class KernelRequestEventListener
{
    public function __construct(private EventService $eventService)
    {
    }

    /**
     * @param RequestEvent $event
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $event->getRequest()->attributes->set(
            key: RequestAttributesEnum::IS_API_REQUEST->value,
            value: $this->isApiRequest($event->getRequest())
        );

        $this->eventService->addBuiltInEvent('kernel.request', '', KernelRequestEventListener::class);
    }

    private function isApiRequest(Request $request): bool
    {
        return str_contains($request->getRequestUri(), '/api');
    }
}