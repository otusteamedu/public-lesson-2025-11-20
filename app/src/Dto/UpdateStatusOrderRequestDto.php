<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateStatusOrderRequestDto
{
    public function __construct(
        #[Assert\Positive(message: 'Идентификатор заказа должен быть больше нуля')]
        public int $orderId,

        #[Assert\NotBlank]
        public string $status
    ) {
    }
}