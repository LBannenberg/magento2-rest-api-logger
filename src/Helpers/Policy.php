<?php

namespace Corrivate\RestApiLogger\Helpers;

class Policy
{
    public bool $forbidRequest = false;
    public bool $forbidResponse = false;
    public bool $censorRequest = false;
    public bool $censorResponse = false;
    public bool $requiredForRequestFailed = false;
    public bool $requiredForResponseFailed = false;
    public ?bool $allowsRequest = null;
    public ?bool $allowsResponse = null;

    public function preventLogRequest(): bool
    {
        return $this->forbidRequest || $this->requiredForRequestFailed || ($this->allowsRequest === false);
    }

    public function preventLogResponse(): bool
    {
        return $this->forbidResponse || $this->requiredForResponseFailed || ($this->allowsResponse === false);
    }
}
