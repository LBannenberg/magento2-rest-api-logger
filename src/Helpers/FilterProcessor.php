<?php

namespace Corrivate\RestApiLogger\Helpers;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Response;

class FilterProcessor
{
    const REQUEST_ASPECTS = ['method', 'route', 'user_agent', 'ip', 'request_body'];
    const RESPONSE_ASPECTS = ['status_code', 'response_body'];
    private Config $config;
    private Policy $policy; // The policy persists from request to response; based on the request, we may decide to limit the response


    public function __construct(
        Config $config
    )
    {
        $this->config = $config;
        $this->policy = new Policy();
    }


    public function processRequest(RequestInterface $request): Policy
    {
        foreach($this->config->getFilterSettings() as $filterSetting) {
            if(!in_array($filterSetting['aspect'], self::REQUEST_ASPECTS )) {
                continue;
            }
            $aspectValue = $this->extractAspectFromRequest($request, $filterSetting['aspect']);
            $match = $this->aspectMatchesCondition($aspectValue, $filterSetting['condition'], $filterSetting['value']);
            $this->updatePolicy($match, $filterSetting['filter']);
        }
        return $this->policy;
    }


    public function processResponse(Response $request): Policy
    {
        foreach($this->config->getFilterSettings() as $filterSetting) {
            if(!in_array($filterSetting['aspect'], self::RESPONSE_ASPECTS )) {
                continue;
            }
            $aspectValue = $this->extractAspectFromResponse($request, $filterSetting['aspect']);
            $match = $this->aspectMatchesCondition($aspectValue, $filterSetting['condition'], $filterSetting['value']);
            $this->updatePolicy($match, $filterSetting['filter']);
        }
        return $this->policy;
    }


    private function aspectMatchesCondition(string $aspectValue, string $condition, string $conditionValue): bool
    {
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
                    $this->policy->forbidRequest = true;
                    $this->policy->forbidResponse = true;
                    return;
                case 'forbid_request':
                    $this->policy->forbidRequest = true;
                    return;
                case 'forbid_response':
                    $this->policy->forbidResponse = true;
                    return;
                case 'censor_both':
                    $this->policy->censorRequest = true;
                    $this->policy->censorResponse = true;
                    return;
                case 'censor_request':
                    $this->policy->censorRequest = true;
                    return;
                case 'censor_response':
                    $this->policy->censorResponse = true;
                    return;

                // A single allow condition is enough to toggle this to success
                case 'allow_both':
                    $this->policy->allowsRequest = true;
                    $this->policy->allowsResponse = true;
                    return;
                case 'allow_request':
                    $this->policy->allowsRequest = true;
                    return;
                case 'allow_response':
                    $this->policy->allowsResponse = true;
                    return;
            }
        }


        switch ($filter) {
            // A single failed require is enough to toggle this to failure
            case 'require_both':
                $this->policy->requiredForRequestFailed = true;
                $this->policy->requiredForResponseFailed = true;
                return;
            case 'require_request':
                $this->policy->requiredForRequestFailed = true;
                return;
            case 'require_response':
                $this->policy->requiredForResponseFailed = true;
                return;

            // If null, set to failed, but don't overwrite success
            case 'allow_both':
                $this->policy->allowsRequest ??= false;
                $this->policy->allowsResponse ??= false;
                return;
            case 'allow_request':
                $this->policy->allowsRequest ??= false;
                return;
            case 'allow_response':
                $this->policy->allowsResponse ??= false;
                return;
        }
    }


    private function extractAspectFromRequest(RequestInterface $request, string $aspect): string
    {
        switch($aspect) {
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
        switch($aspect) {
            case 'status_code':
                return (string)$response->getStatusCode();
            case 'response_body':
                return (string)$response->getBody();
        }
        return '';
    }
}
