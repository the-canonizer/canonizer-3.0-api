<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Helpers\ResponseInterface;

class UpdateNicknameRequest extends FormRequest
{
    protected $resProvider;

    public function __construct(ResponseInterface $resProvider)
    {
        parent::__construct();
        $this->resProvider = $resProvider;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'visibility_status' => 'required',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->messages();
        
        throw new HttpResponseException(
            $this->resProvider->apiJsonResponse(422, "The given data was invalid.", '', $errors)
        );
    }
}
