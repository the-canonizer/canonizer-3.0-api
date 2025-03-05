<?php
namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="Canonizer API",
 *         description="API documentation for Canonizer",
 *         termsOfService="http://canonizer.com/terms",
 *         @OA\Contact(
 *             email="support@canonizer.com"
 *         )
 *     ),
 *     @OA\Server(
 *         description="Local API Server",
 *         url="http://canonizer.local/api/v3"
 *     ),
 *     @OA\Components(
 *         @OA\SecurityScheme(
 *             securityScheme="clientAuth",
 *             type="http",
 *             scheme="bearer",
 *             bearerFormat="JWT",
 *             description="Enter your bearer token in the text input below. Obtain your token by making a POST request to /client-token API."
 *         ),
 *         @OA\SecurityScheme(
 *              securityScheme="loginAuthToken",
 *              type="http",
 *              scheme="bearer",
 *              bearerFormat="JWT",
 *              description="Enter your bearer token in the text input below. Obtain your token by making a POST request to /user/login API."
 *         ),
 *         @OA\Response(
 *             response="400BadRequest",
 *             description="Bad Request",
 *             @OA\JsonContent(
 *                 type="object",
 *                 @OA\Property(property="status_code", type="integer", example=400),
 *                 @OA\Property(property="message", type="string", example="Invalid request parameters"),
 *                 @OA\Property(property="error", type="string", nullable=true)
 *             )
 *         ),
 *         @OA\Response(
 *             response="404NotFound",
 *             description="Resource Not Found",
 *             @OA\JsonContent(
 *                 type="object",
 *                 @OA\Property(property="status_code", type="integer", example=404),
 *                 @OA\Property(property="message", type="string", example="Record not found"),
 *                 @OA\Property(property="error", type="string", nullable=true)
 *             )
 *         )
 *     )
 * )
 */
class OpenApiSpec
{
}
