<?php

namespace App\Http\Request;

class ValidationRules
{
    public function getTokenValidationRules(): array
    {
        return ([
            'client_id' => 'required',
            'client_secret' => 'required',
        ]);
    }

    public function getLoginValidationRules(): array
    {
        return ([
            'username' => 'required',
            'password' => 'required',
            'client_id' => 'required',
            'client_secret' => 'required',
        ]);
    }
}
