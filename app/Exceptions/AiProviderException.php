<?php

namespace App\Exceptions;

class AiProviderException extends \RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
