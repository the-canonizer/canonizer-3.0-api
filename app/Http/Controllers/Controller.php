<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;
use App\Http\Request\ValidationRules;
use App\Http\Request\ValidationMessages;
class Controller extends BaseController
{
    /**
     * Common response among all the classes.
     *
     * @var mixed
     */
    protected $resProvider;
    protected $resourceProvider;
    protected $rules;
    protected $validationMessages;

    public function __construct(ResponseInterface $resProvider, ResourceInterface $resourceProvider, ValidationRules $rules, ValidationMessages $validationMessages)
    {
        $this->resProvider = $resProvider;
        $this->resourceProvider = $resourceProvider;
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
    }
}
