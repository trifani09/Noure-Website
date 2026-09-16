<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator as LaravelValidator;

abstract class CatalogRequest extends FormRequest
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

    protected function withValidator(LaravelValidator $validator): void
    {
        $validator->after(function (LaravelValidator $validator): void {
            $allowed = array_keys($this->rules());

            foreach (array_keys($this->query()) as $parameter) {
                if (! in_array($parameter, $allowed, true)) {
                    $validator->errors()->add($parameter, 'The selected filter is not supported.');
                }
            }
        });
    }
}
