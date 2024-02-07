<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Filter;

use Corrivate\RestApiLogger\Model\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Response;

class MainFilter
{
    private Config $config;


    // Internal filter policy state
    private bool $forbidRequest = false;
    private bool $forbidResponse = false;
    private bool $censorRequest = false;
    private bool $censorResponse = false;
    private bool $requiredForRequestFailed = false;
    private bool $requiredForResponseFailed = false;
    private ?bool $allowsRequest = null;
    private ?bool $allowsResponse = null;


    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }


    /**
     * @return bool[]
     */
    public function processRequest(RequestInterface $request): array
    {
        foreach ($this->config->getRequestFilters() as $filter) {
            if (
                ($this->forbidRequest || $this->requiredForRequestFailed)
                && ($this->forbidResponse || $this->requiredForResponseFailed)
            ) {
                break; // No need to process further rules
            }
            $aspectValue = $this->extractAspectFromRequest($request, $filter->aspect);
            $match = $this->aspectMatchesCondition($aspectValue, $filter->condition, $filter->value);
            $this->updatePolicy($match, $filter->consequence);
        }
        return [$this->shouldLogRequest(), $this->censorRequest];
    }


    /**
     * @return bool[]
     */
    public function processResponse(Response $request): array
    {
        foreach ($this->config->getResponseFilters() as $filter) {
            if ($this->forbidResponse || $this->requiredForResponseFailed) {
                break; // No need to process further rules
            }
            $aspectValue = $this->extractAspectFromResponse($request, $filter->aspect);
            $match = $this->aspectMatchesCondition($aspectValue, $filter->condition, $filter->value);
            $this->updatePolicy($match, $filter->consequence);
        }
        return [$this->shouldLogResponse(), $this->censorResponse];
    }


    private function aspectMatchesCondition(string $aspectValue, string $condition, string $conditionValue): bool
    {
        $aspectValue = strtolower($aspectValue);
        $conditionValue = strtolower($conditionValue);
        switch ($condition) {
            case 'contains':
                return (strpos($aspectValue, $conditionValue) !== false);
            case 'does not contain':
                return (strpos($aspectValue, $conditionValue) === false);
            case '=':
                return $aspectValue == $conditionValue;
            case '!=':
                return $aspectValue != $conditionValue;
            case '>=':
                return $aspectValue >= $conditionValue;
            case '>':
                return $aspectValue > $conditionValue;
            case '<=':
                return $aspectValue <= $conditionValue;
            case '<':
                return $aspectValue < $conditionValue;
            default:
                return false; // Shouldn't be possible
        }
    }


    private function updatePolicy(bool $match, string $filter): void
    {
        if ($match) {
            switch ($filter) {
                case 'forbid_both':
                    $this->forbidRequest = true;
                    $this->forbidResponse = true;
                    return;
                case 'forbid_request':
                    $this->forbidRequest = true;
                    return;
                case 'forbid_response':
                    $this->forbidResponse = true;
                    return;
                case 'censor_both':
                    $this->censorRequest = true;
                    $this->censorResponse = true;
                    return;
                case 'censor_request':
                    $this->censorRequest = true;
                    return;
                case 'censor_response':
                    $this->censorResponse = true;
                    return;

                // A single allow condition is enough to toggle this to success
                case 'allow_both':
                    $this->allowsRequest = true;
                    $this->allowsResponse = true;
                    return;
                case 'allow_request':
                    $this->allowsRequest = true;
                    return;
                case 'allow_response':
                    $this->allowsResponse = true;
                    return;
            }
        }


        if (!$match) {
            switch ($filter) {
                // A single failed require is enough to toggle this to failure
                case 'require_both':
                    $this->requiredForRequestFailed = true;
                    $this->requiredForResponseFailed = true;
                    return;
                case 'require_request':
                    $this->requiredForRequestFailed = true;
                    return;
                case 'require_response':
                    $this->requiredForResponseFailed = true;
                    return;

                // If null, set to failed, but don't overwrite success
                case 'allow_both':
                    $this->allowsRequest ??= false;
                    $this->allowsResponse ??= false;
                    return;
                case 'allow_request':
                    $this->allowsRequest ??= false;
                    return;
                case 'allow_response':
                    $this->allowsResponse ??= false;
                    return;
            }
        }
    }


    private function extractAspectFromRequest(RequestInterface $request, string $aspect): string
    {
        switch ($aspect) {
            case 'method':
                return strtoupper($request->getMethod());
            case 'route':
                return $request->getRequestUri();
            case 'ip':
                return $request->getClientIp();
            case 'user_agent':
                return $request->getHeader('User-Agent') ?? '';
            case 'request_body':
                return (string)$request->getContent();
        }
        return '';
    }


    private function extractAspectFromResponse(Response $response, string $aspect): string
    {
        switch ($aspect) {
            case 'status_code':
                return (string)$response->getStatusCode();
            case 'response_body':
                return (string)$response->getBody();
        }
        return '';
    }


    private function shouldLogRequest(): bool
    {
        return !$this->forbidRequest
            && !$this->requiredForRequestFailed
            && $this->allowsRequest !== false;
    }


    private function shouldLogResponse(): bool
    {
        return !$this->forbidResponse
            && !$this->requiredForResponseFailed
            && $this->allowsResponse !== false;
    }
}
