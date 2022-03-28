<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Http\Request;
use Anik\Form\FormRequest;
use App\Helpers\ResponseInterface;

class AddFolderRequest extends FormRequest
{
    /**
     * The sanitized input.
     *
     * @var array
     */
    protected $sanitized;

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


    public function validate(): void
    {
        if (false === $this->authorize()) {
            $this->failedAuthorization();
        }
      

        $this->validator = $this->app->make('validator')
                                     ->make($this->sanitizeInput(), $this->rules(), $this->messages(), $this->attributes());

        if ($this->validator->fails()) {
            $this->validationFailed();
        }

        $this->validationPassed();
    }



    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|unique:file_folder|max:50'
        ];
    }

    protected function messages(): array
    {
        return [
           'name.required' => "Folder name is required",
           'name.max' => "Folder name can not be greater than 50 characters.",
           'name.unique' => "Folder name already exists, please try another one"
        ];
    }

    protected function errorResponse(): ?JsonResponse
    { 
        return $this->resProvider->apiJsonResponse(422, $this->errorMessage(), '', $this->validator->errors()->messages());
    } 

     /**
     * Sanitize the input.
     *
     * @return array
     */
    protected function sanitizeInput()
    {   $arr = [];
        foreach($this->all() as $key => $input){
           $arr[$key] = trim($input);
        }
        $this->merge($arr);
        return $this->all();

    }

    
}
