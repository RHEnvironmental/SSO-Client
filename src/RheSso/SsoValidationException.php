<?php

namespace RheSso;

use Exception;
use Throwable;

class SsoValidationException extends Exception
{
    private $errors;

    public function __construct(array $errors, Throwable $previousError = null)
    {
        parent::__construct(
            'Request data sent to the RHE SSO API did not pass validation rules.',
            0,
            $previousError
        );

        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}