<?php

namespace App\Http\Request;

use Anik\Form\FormRequest;
use App\Helpers\ResponseInterface;

class NewsFeedRequest extends FormRequest
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
        if ($this->method() == 'GET') {
            return [
                'topicnum' => 'required',
                'parentcampnum' => 'required',
            ];
        }
    }
    protected function apiJsonResponse(): ?apiJsonResponse
    {
        return $this->resProvider->apiJsonResponse(422, $this->errorMessage(), '', $this->validator->errors()->messages());
    }
}
