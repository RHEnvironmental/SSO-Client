<?php

namespace RheSso;

use Exception;

class SsoAuthException extends Exception
{
    private $data;

    public function __construct($message = null, $data)
    {
        parent::__construct($message, 0, null);

        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}