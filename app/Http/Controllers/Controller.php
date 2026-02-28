<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseInterface;
use AllowDynamicProperties;

#[AllowDynamicProperties]
abstract class Controller
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
