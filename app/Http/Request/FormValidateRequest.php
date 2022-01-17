<?php

use Illuminate\Http\Request;

abstract class FormValidateRequest {
    abstract public function validate(Request $request, $rules, $messages);
}


