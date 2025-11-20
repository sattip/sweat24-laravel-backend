<?php

namespace App\Exceptions;

use Exception;

class BusinessValidationException extends Exception
{
    protected $errorCode;

    public function __construct(string $message, string $errorCode = null)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}





