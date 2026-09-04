<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base API resource wrapping payloads as { data, message }.
 */
class ApiResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = 'data';

    /**
     * Optional flash-style message for the client.
     */
    protected ?string $apiMessage = null;

    /**
     * Attach a message to the resource response.
     */
    public function withMessage(?string $message): static
    {
        $this->apiMessage = $message;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        if ($this->apiMessage === null) {
            return [];
        }

        return [
            'message' => $this->apiMessage,
        ];
    }

    /**
     * Whether the current user may delete this resource, per its policy.
     */
    protected function canDelete(Request $request): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        return $user->can('delete', $this->resource);
    }

    /**
     * Build a successful JSON envelope without a resource instance.
     */
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = ['data' => $data];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload, $status);
    }

    /**
     * Build an error JSON envelope.
     *
     * @param  array<string, mixed>|null  $errors
     */
    public static function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $payload = [
            'data' => null,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
