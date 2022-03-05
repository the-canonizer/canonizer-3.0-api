<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Helpers\ResponseInterface;
use App\Helpers\LoggerInterface;

class Controller extends BaseController
{
    /**
     * Common response among all the classes.
     *
     * @var mixed
     */
    protected $resProvider;

    /**
     * Custom Log.
     *
     * @var mixed
     */
    protected $logger;

    public function __construct(ResponseInterface $resProvider, LoggerInterface $logger)
    {
        $this->resProvider = $resProvider;
        $this->logger = $logger;
    }
}
