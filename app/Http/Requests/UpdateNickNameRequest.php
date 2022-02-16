<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Http\Request;
use Anik\Form\FormRequest;

class UpdateNicknameRequest extends FormRequest
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
        return [
            'visibility_status' => 'required',
        ];
    }

    protected function messages(): array
    {
        return [];
    }

    protected function errorResponse(): ?JsonResponse
    { 
        return response()->json([
            'status_code'=> 400,
            'data' => null,
            'message' => $this->errorMessage(),
            'errors' => $this->validator->errors()->messages(),
        ], 400);
    } 

    
}
