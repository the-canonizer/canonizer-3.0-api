<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveTopicsRequest extends FormRequest
{
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
        switch ($this->method()) {

            case 'POST': {
                    return [
                        'topic_numbers' => 'required|array',
                    ];
                }
            case 'GET': {
                    return [
                        'topic_numbers' => 'required|array',
                    ];
            }
            default:
                return [];
        }
    }
}
