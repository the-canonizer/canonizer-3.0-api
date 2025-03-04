<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;
/**
     * @OA\OpenApi(
        * @OA\Info(
            * version="1.0.0",
            * title="Canonizer API",
            * description="API documentation for Canonizer",
            * termsOfService="http://canonizer.com/terms",
            * @OA\Contact(
                * email="support@canonizer.com"
            * )
        * ),
        * @OA\Server(
            * description="Local API Server",
            * url="http://canonizer.local/api/v3"
        * ),
        * @OA\Components(
            * @OA\SecurityScheme(
            * securityScheme="bearerAuth",
            * type="http",
            * scheme="bearer",
            * bearerFormat="JWT",
            * description="Enter your bearer token in the text input below. Obtain your token by making a POST request to /client-token API Or user/login API."
        * )
        * )
    * )
*/
class OpenApiSpec {}
