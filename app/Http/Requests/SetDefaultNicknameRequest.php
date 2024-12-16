<?php

namespace App\Http\Requests;

use Anik\Form\FormRequest;

class SetDefaultNicknameRequest extends FormRequest
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
            'nick_name_id' => 'required|exists:nick_name,id',
        ];
    }

    protected function messages(): array
    {
        return [
            'nick_name_id.required' => 'nick_name_id is required',
            'nick_name_id.exists' => 'nick_name_id does not exist',
        ];
    }
}
