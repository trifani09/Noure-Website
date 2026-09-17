<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = [];
        foreach ($validator->errors()->messages() as $field => $messages) {
            foreach ($messages as $message) {
                $errors[] = ['code' => 'invalid', 'field' => $field, 'message' => $message];
            }
        }

        throw new HttpResponseException(response()->json([
            'data' => null,
            'meta' => ['errors' => $errors],
            'message' => 'Validation failed.',
        ], 422));
    }
}
