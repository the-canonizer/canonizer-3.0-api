<?php

namespace App\Http\Request;

use Anik\Form\FormRequest;
use  App\Helpers\ResponseInterface;

class ImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    protected function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    protected function rules(): array
    {
        if ($this->method() == 'POST') {
            return [
                'page_name' => 'required|string'
            ];
        }
    }

    /**
     * format the validation response if there is error in validation.
     *
     * @return array
     */
    protected function apiJsonResponse()
    {

        return response()->json([
            'status_code' => 422,
            'message' => 'validation errors',
            'data' => null,
            'errors' => [
                'message' => 'The given data is invalid',
                'errors' => $this->validator->errors()->messages()
            ]
        ], $this->statusCode());
    }
}