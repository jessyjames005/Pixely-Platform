<?php

declare(strict_types=1);

namespace App\Core\Api\Error;

/**
 * Represents a standard API error.
 *
 * This value object provides a consistent error structure
 * that can be shared by all Pixely Platform extensions.
 */
final readonly class ApiError
{
    /**
     * Create a new API error.
     *
     * @param array<string, mixed>|null $details
     */
    public function __construct(
        public string $code,
        public string $message,
        public ?array $details = null,
    ) {
    }

    /**
     * Convert the error to an API response payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $error = [
            'code' => $this->code,
            'message' => $this->message,
        ];

        if ($this->details !== null) {
            $error['details'] = $this->details;
        }

        return [
            'error' => $error,
        ];
    }
}
