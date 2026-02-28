<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;

class TreeStoreRequest extends FormRequest
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
            case 'POST':
                return [
                    'topic_num' => 'required|gte:1|integer|max:'.PHP_INT_MAX.'|exists:topic,topic_num',
                    'asofdate' => 'required',
                    'algorithm' => 'required|string',
                    'update_all' => 'in:0,1',
                    'model_id' => 'nullable|integer',
                    'model_type' => 'nullable|string|in:topic,camp,statement',
                    'job_type' => 'nullable|string|in:live-time-job',
                    'event_type' => 'nullable|string',
                    'pre_LiveId' => 'nullable|string',
                    'camp_num' => 'integer|gte:1|max:' . PHP_INT_MAX,
                ];
            case 'GET':
                return [
                    'topic_num' => 'required|integer',
                    'asofdate' => 'required',
                    'algorithm' => 'required|string'
                ];
            default:
                return [];
        }
    }

    public function messages(): array
    {
        return [
            'topic_num.exists' => 'Topic not found.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->messages();
        
        $statusCode = 422;
        if (array_key_exists("topic_num", $errors)) {
            if (in_array('Topic not found.', $errors['topic_num']) || in_array('The selected topic num is invalid.', $errors['topic_num'])) {
                $statusCode = 404;
            }
        }

        $response = response()->json([
            'code' => $statusCode,
            'message' => 'The given data was invalid.',
            'errors' => $errors,
            'data' => null,
            'success' => false,
        ], $statusCode);
    
        throw new HttpResponseException($response);
    }
}
