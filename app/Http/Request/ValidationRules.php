<?php

class ValidationRules
{
    public function getTokenValidationRules(): array
    {
        return ([
            'client_id' => 'required',
            'client_secret' => 'required',
        ]);
    }
}
