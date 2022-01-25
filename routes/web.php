<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
$router->get('/key', function() {
    return \Illuminate\Support\Str::random(32);
});
$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api/v3'], function() use ($router)
{
    $router->post('/client_token','UserController@clientToken');

    //Route Group to access api with client token
    $router->group(['middleware' => 'client'], function() use ($router) {
        $router->post('/register','UserController@createUser');
        $router->post('/user/login','UserController@loginUser');
    });

    //Route Group to access api with user access token
    $router->group(['middleware' => 'auth'], function() use ($router) {
        $router->get('/user/logout','UserController@logoutUser');
        $router->post('changepassword','ProfileController@changePassword');
        $router->get('mobilecarrier','ProfileController@mobileCarrier');
        $router->post('updateprofile','ProfileController@updateProfile');
    });
});
