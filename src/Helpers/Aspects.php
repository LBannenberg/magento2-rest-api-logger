<?php

namespace Corrivate\RestApiLogger\Helpers;

class Aspects
{
    // These properties match those in \Corrivate\RestApiLogger\Block\Adminhtml\Form\Field\Aspect
    public string $method = '';
    public string $route = '';
    public string $user_agent = '';
    public string $ip = '';
    public string $request_body = '';
    public string $status_code = '';
    public string $response_body = '';
    public function title(): string
    {
        return implode(' ', [
            $this->ip,
            $this->user_agent,
            $this->method,
            $this->route
        ]);
    }
}
