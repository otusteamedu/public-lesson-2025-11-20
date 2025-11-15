<?php

namespace App\EventListener;

use App\Request\ApiRequestCheckTrait;
use App\Response\ApiResponse;
use App\Service\EventService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final readonly class KernelExceptionEventListener
{
    use ApiRequestCheckTrait;

    public function __construct(
        private Environment $twig,
        private EventService $eventService,
        private SerializerInterface $serializer
    ) {
    }

    /**
     * @param ExceptionEvent $event
     * @return void
     *
     * @throws InvalidArgumentException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws ExceptionInterface
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $code = $this->resolveCode($exception);

        if ($this->isApiRequest($event->getRequest())) {
            $apiResponse = ApiResponse::createError(null, $exception->getMessage(), $code);

            $response = new JsonResponse(
                data: $this->serializer->serialize($apiResponse, JsonEncoder::FORMAT),
                status: $code,
                json: true
            );
        } else {
            $response = new Response(
                $this->twig->render('error.html.twig', ['message' => $exception->getMessage()]),
                $code
            );
        }

        $event->setResponse($response);

        $this->eventService->addBuiltInEvent(
            'kernel.exception',
            $exception->getMessage(),
            KernelExceptionEventListener::class
        );
    }

    private function resolveCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}