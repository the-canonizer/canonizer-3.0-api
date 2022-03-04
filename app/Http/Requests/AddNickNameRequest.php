<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Http\Request;
use Anik\Form\FormRequest;
use App\Helpers\ResponseInterface;

class AddNickNameRequest extends FormRequest
{

    public function __construct(ResponseInterface $resProvider)
    {
        $this->resProvider = $resProvider;
    }

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
            'nick_name' => 'required|unique:nick_name|max:50',
            'visibility_status' => 'required',
        ];
    }

    protected function messages(): array
    {
        return [
          'nick_name.unique' => "Nick name already exists, please try another one.",
          'nick_name.max' => "Nick name can not be more than 50 characters."

        ];
    }

    protected function errorResponse(): ?JsonResponse
    { 
        return $this->resProvider->apiJsonResponse(422, $this->errorMessage(), '', $this->validator->errors()->messages());
    } 

    
}
