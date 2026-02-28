<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TopicRequest extends FormRequest
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
                        'page_number' => 'required|integer',
                        'page_size' => 'required|integer',
                        'namespace_id' => 'nullable|integer',
                        'asofdate' => 'required',
                        'algorithm' => 'required|string',
                        'asof' => 'required|string',
                        'search' => 'nullable|string',
                        'page' => 'nullable|string',
                        'topic_tags' => 'array', // Ensure it is an array
                        'topic_tags.*' => 'integer', // Ensure each element in the array is an integer
                    ];
                }
            default:
                return [];
        }
    }
}
