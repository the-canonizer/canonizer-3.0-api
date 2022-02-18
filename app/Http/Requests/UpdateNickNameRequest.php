<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Http\Request;
use Anik\Form\FormRequest;
use App\Helpers\ResponseInterface;

class UpdateNicknameRequest extends FormRequest
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
            'visibility_status' => 'required',
        ];
    }

    protected function messages(): array
    {
        return [0];
    }

    protected function errorResponse(): ?JsonResponse 
    { 

        return $this->resProvider->apiJsonResponse(422, $this->errorMessage(), '', $this->validator->errors()->messages());
       
    } 

    
}
