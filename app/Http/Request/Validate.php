<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class Validate extends FormValidateRequest
{
    public function __construct()
    {
        // Might be used
    }

    public function validate(Request $request, $rules, $messages)
    {
        $validator = Validator::make($request->all(), $rules, $messages);

        if ( $validator->fails() ) {
            return (object)[
                "status_code" => 400,
                "message"     => "The given data was invalid.",
                "error"       => $validator->errors(),
                "data"        => null
            ];
        }
        return 1;
    }
}
