<?php

namespace App\EventListener;

use App\Response\ApiResponse;
use App\Service\EventService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class KernelViewEventListener
{
    public function __construct(
        private EventService $eventService,
        private SerializerInterface $serializer
    ) {
    }

    /**
     * @param ViewEvent $event
     * @return void
     * 
     * @throws InvalidArgumentException
     * @throws ExceptionInterface
     */
    public function onKernelView(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();

        if ($controllerResult instanceof ApiResponse) {
            $jsonResponse = new JsonResponse(
                data: $this->serializer->serialize($controllerResult, JsonEncoder::FORMAT),
                status: $controllerResult->code,
                json: true
            );

            $event->setResponse($jsonResponse);
        }

        $this->eventService->addBuiltInEvent('kernel.view', '', KernelViewEventListener::class);
    }
}