<?php

namespace App\Helpers;

use App\Enums\StatusCodeEnum;
use Illuminate\Http\JsonResponse;

class ResponseFormatter
{
    protected array $response = [];
    protected int $code;
    protected string $status;
    protected ?string $message;
    protected mixed $data;

    public function __construct(string $status = 'success', ?string $message = null, mixed $data = null, int|StatusCodeEnum $code = StatusCodeEnum::SUCCESS)
    {
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
        $this->code = $code instanceof StatusCodeEnum ? $code->value : $code;

        $this->buildResponse();
    }

    protected function buildResponse(): void
    {
        $this->response = [
            'meta' => [
                'code' => $this->code,
                'status' => $this->status,
                'message' => $this->message,
            ],
            'data' => $this->data,
        ];

    }

    public static function success(mixed $data = null, ?string $message = null): self
    {
        return new self('success', $message, $data, StatusCodeEnum::SUCCESS);
    }

    public static function error(?string $message = null, int|StatusCodeEnum $code = StatusCodeEnum::INTERNAL_SERVER_ERROR, mixed $data = null): self
    {
        return new self('error', $message, $data, $code);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function toResponse(): JsonResponse
    {
        return new JsonResponse($this->response, $this->code);
    }
}
