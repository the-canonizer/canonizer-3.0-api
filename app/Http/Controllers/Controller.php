<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Helpers\ResponseInterface;
class Controller extends BaseController
{
    /**
     * Common response among all the classes.
     *
     * @var mixed
     */
    protected $resProvider;

    public function __construct(ResponseInterface $resProvider)
    {
        $this->resProvider = $resProvider;
    }
}
