<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseInterface;
use AllowDynamicProperties;
use Laravel\Lumen\Routing\Controller as BaseController;

#[AllowDynamicProperties]
abstract class Controller extends BaseController
{
    /**
     * Common response among all the classes.
     *
     * @var ResponseInterface
     */
    protected $resProvider;

    public function __construct(ResponseInterface $resProvider)
    {
        $this->resProvider = $resProvider;
    }
}
