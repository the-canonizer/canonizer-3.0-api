<?php
namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="ExceptionRes",
 *     type="object",
 *     title="Exception Response",
 *     description="Response schema for exceptions",
 *     @OA\Property(
 *         property="error",
 *         type="string",
 *         description="Error message"
 *     ),
 *     @OA\Property(
 *         property="code",
 *         type="integer",
 *         description="Error code"
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User schema",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="User ID"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="User name"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         description="User email"
 *     )
 * )
 */
class Schemas
{
    // This class is intentionally left empty.
}
