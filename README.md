# Lumen PHP Framework

[![Build Status](https://travis-ci.org/laravel/lumen-framework.svg)](https://travis-ci.org/laravel/lumen-framework)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel/framework)](https://packagist.org/packages/laravel/lumen-framework)
[![Latest Stable Version](https://img.shields.io/packagist/v/laravel/framework)](https://packagist.org/packages/laravel/lumen-framework)
[![License](https://img.shields.io/packagist/l/laravel/framework)](https://packagist.org/packages/laravel/lumen-framework)

Laravel Lumen is a stunningly fast PHP micro-framework for building web applications with expressive, elegant syntax. We believe development must be an enjoyable, creative experience to be truly fulfilling. Lumen attempts to take the pain out of development by easing common tasks used in the majority of web projects, such as routing, database abstraction, queueing, and caching.

## Official Documentation

Documentation for the framework can be found on the [Lumen website](https://lumen.laravel.com/docs).

## Activity Logger Laravel
   **Add logs automatically**
   
   If you want to log the activities automatically on model created, updated, deleted events then extend    model with BaseModel.
   Here are some properties in Base Model that you can override in specific model
1. **$logName:** Default value of $logName is ‘BaseModel’. It can be override in model
2. **$recordedEvents:** Specify the event such   created, updated, deleted
3. **getDescriptionForEvent:**  Log Description can be changed through this function

  **Add logs Manually in controller**
  
  Logs can be added manually in controller.
 When you extend the YourContoller with Controller, there is an object **$this→logger** which is  available in whole controller. 
 How you can log the activity manually in controller
 
        $this→logger::createLog($description,  $eloquentModelObject, $logType, $withProperties);

**$description:**  the description of activity

**$eloquentModel:**  Specify the model against log is being created

**$logType:** Log Type can model name or anything you want to store like user logs, topic logs,

**$withProperties:** Add extra keys or properties in array

 **Example**

          $description = “User B has delegated his support to User A”
          
          $logType = “Support”
          
          $withProperties = [“delegater_id” => 1]
          
          $this→logger::createLog($description,  new Support(),  $logType, $withProperties);

## Contributing

Thank you for considering contributing to Lumen! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Security Vulnerabilities

If you discover a security vulnerability within Lumen, please send an e-mail to Taylor Otwell at taylor@laravel.com. All security vulnerabilities will be promptly addressed.

## License

The Lumen framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
