<?php

namespace RheSso;

class SsoValidationException extends \Exception
{
    private $errors;

    public function __construct(array $errors)
    {
        parent::__construct('Request data sent to the RHE SSO API did not pass validation rules.');

        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}