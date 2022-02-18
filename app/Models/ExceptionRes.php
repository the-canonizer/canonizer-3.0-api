<?php

namespace App\Models;


/**
 * * @OA\Schema(
 *     schema="ExceptionRes",
 *     title="Exception Response of the API's",
 * 	    @OA\Property(
 *         property="status_code",
 *         type="integer"
 *     ),
 * 	    @OA\Property(
 *         property="message",
 *         type="string"
 *      ),
 *      @OA\Property (
 *          property="error",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="data",
 *          type="string"
 *      )
 * )
 */
class ExceptionRes
{

}
