<?php

namespace App\Services\Ocr;

use RuntimeException;

class OcrProviderException extends RuntimeException
{
    public function __construct(string $message, protected int $statusCode = 503)
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
