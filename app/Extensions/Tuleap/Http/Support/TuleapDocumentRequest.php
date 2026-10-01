<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Support;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use LaravelJsonApi\Core\Exceptions\JsonApiException;

final class TuleapDocumentRequest
{
    public static function attributes(
        Request $request,
        string $type,
        array $allowed,
        array $rules = [],
    ): array {
        $document = $request->json()->all();
        $data = $document['data'] ?? null;

        if (! is_array($data) || ($data['type'] ?? null) !== $type || ! is_array($data['attributes'] ?? null)) {
            throw JsonApiException::error([
                'status' => 400,
                'code' => 'INVALID_JSON_API_DOCUMENT',
                'title' => 'Bad Request',
                'detail' => 'The request must contain data.type and data.attributes for the expected resource type.',
                'source' => ['pointer' => '/data'],
            ]);
        }

        $attributes = $data['attributes'];
        $unknown = array_diff(array_keys($attributes), $allowed);
        if ($unknown !== []) {
            $field = (string) reset($unknown);
            throw JsonApiException::error([
                'status' => 400,
                'code' => 'UNKNOWN_ATTRIBUTE',
                'title' => 'Bad Request',
                'detail' => "The {$field} attribute is not supported.",
                'source' => ['pointer' => "/data/attributes/{$field}"],
            ]);
        }

        $validator = Validator::make($attributes, $rules);
        if ($validator->fails()) {
            self::throwValidationError($validator);
        }

        return $attributes;
    }

    private static function throwValidationError(ValidatorContract $validator): never
    {
        $field = (string) array_key_first($validator->errors()->messages());
        $message = $validator->errors()->first($field);

        throw JsonApiException::error([
            'status' => 422,
            'code' => 'VALIDATION_ERROR',
            'title' => 'Unprocessable Entity',
            'detail' => $message,
            'source' => ['pointer' => "/data/attributes/{$field}"],
        ]);
    }
}
