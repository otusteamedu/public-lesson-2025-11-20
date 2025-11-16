<?php

namespace App\ArgumentValueResolver;

use App\Dto\UpdateStatusOrderRequestDto;
use App\Service\EventService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsTargetedValueResolver('update_status_order_request')]
final readonly class UpdateStatusOrderRequestDtoResolver implements ValueResolverInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private EventService $eventService
    ) {
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return iterable
     *
     * @throws ExceptionInterface
     * @throws InvalidArgumentException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $this->eventService->addBuiltInEvent(
            eventName: 'kernel.argument_value_resolver',
            message: $argument->getType(),
            source: UpdateStatusOrderRequestDtoResolver::class
        );

        if ($argument->getType() !== UpdateStatusOrderRequestDto::class) {
            return [];
        }

        $dto = $this->serializer->deserialize(
            data: $request->getContent(),
            type: UpdateStatusOrderRequestDto::class,
            format: JsonEncoder::FORMAT
        );

        $validationErrors = $this->validator->validate($dto);
        if (count($validationErrors) > 0) {
            $violations = [];
            foreach ($validationErrors as $violation) {
                $violations[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
            }

            throw new ValidatorException(implode("; ", $violations));
        }

        yield $dto;
    }
}