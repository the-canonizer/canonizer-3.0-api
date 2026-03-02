<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Helpers\ResponseInterface;

class AddNickNameRequest extends FormRequest
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
            'nick_name' => 'required|unique:nick_name|max:50',
            'visibility_status' => 'required',
            'default' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nick_name.required' => "Nick name is required",
            'nick_name.unique' => "Nick name already exists, please try another one.",
            'nick_name.max' => "Nick name can not be more than 50 characters.",
            'default.required' => "Default field is required",
            'default.boolean' => "Default field should be either 0|1 or true|false.",
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->messages();
        
        throw new HttpResponseException(
            $this->resProvider->apiJsonResponse(422, "The given data was invalid.", '', $errors)
        );
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $arr = [];
        foreach ($this->all() as $key => $input) {
            $arr[$key] = is_string($input) ? trim($input) : $input;
        }
        $this->merge($arr);
    }
}
