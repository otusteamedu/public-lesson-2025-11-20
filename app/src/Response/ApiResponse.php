<?php

namespace App\Response;

readonly class ApiResponse
{
    public function __construct(
        public bool $result,
        public mixed $data,
        public ?string $message,
        public int $code
    ) {
    }

    public static function createSuccess(mixed $data, ?string $message, int $code): ApiResponse
    {
        return new self(true, $data, $message, $code);
    }

    public static function createError(mixed $data, ?string $message, int $code): ApiResponse{
        return new self(false, $data, $message, $code);
    }
}