<?php

namespace App\Dto;

readonly class OrderItemDto
{
    public function __construct(
        public int $id,
        public string $createdAt,
        public ?string $updatedAt,
        public string $createdBy,
        public string $status,
        public string $orderContent
    ) {
    }
}