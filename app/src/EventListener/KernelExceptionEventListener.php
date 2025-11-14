<?php

namespace App\EventListener;

use App\Service\EventService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final readonly class KernelExceptionEventListener
{
    public function __construct(
        private Environment $twig,
        private EventService $eventService
    ) {
    }

    /**
     * @param ExceptionEvent $event
     * @return void
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws InvalidArgumentException
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $code = $this->resolveCode($exception);

        if ($this->isApiRequest($event->getRequest())) {
            $response = new JsonResponse(
                [
                    'result' => false,
                    'message' => $exception->getMessage(),
                ],
                $code
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

    private function isApiRequest(Request $request): bool
    {
        return str_contains($request->getRequestUri(), '/api');
    }
}