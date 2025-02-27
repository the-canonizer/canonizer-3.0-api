<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="Canonizer API",
 *         description="Canonizer API documentation",
 *         termsOfService="http://canonizer.com/termsandservice",
 *         @OA\Contact(
 *             email="support@canonizer.com"
 *         )
 *     ),
 *     @OA\Server(
 *         description="Canonizer API",
 *         url="https://api.canonizer.com/api/v3/"
 *     )
 * )
 */
class OpenApiSpec
{
}
