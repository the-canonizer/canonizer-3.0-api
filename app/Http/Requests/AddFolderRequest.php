<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Helpers\ResponseInterface;

class AddFolderRequest extends FormRequest
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
            'name' => 'required|unique:file_folder|max:50'
        ];
    }

    public function messages(): array
    {
        return [
           'name.required' => "Folder name is required",
           'name.max' => "Folder name can not be greater than 50 characters.",
           'name.unique' => "Folder name already exists, please try another one"
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
