<?php

namespace Corrivate\RestApiLogger\Filter;

use Corrivate\RestApiLogger\Model\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Response;
use Psr\Log\LoggerInterface;

class CustomFilter
{
    const REQUEST_ASPECTS = ['method', 'route', 'user_agent', 'ip', 'request_body'];
    const RESPONSE_ASPECTS = ['status_code', 'response_body'];
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
    private LoggerInterface $logger;


    public function __construct(
        Config          $config,
        LoggerInterface $logger
    )
    {
        $this->config = $config;
        $this->logger = $logger;
    }


    /**
     * @return bool[]
     */
    public function processRequest(RequestInterface $request): array
    {
        foreach ($this->config->getFilterSettings() as $filterSetting) {
            if (!in_array($filterSetting['aspect'], self::REQUEST_ASPECTS)) {
                continue;
            }
            $aspectValue = $this->extractAspectFromRequest($request, $filterSetting['aspect']);
            $match = $this->aspectMatchesCondition($aspectValue, $filterSetting['condition'], $filterSetting['value']);
            $this->updatePolicy($match, $filterSetting['filter']);
//            $this->reportMatch('request', $aspectValue, $filterSetting['condition'], $filterSetting['value'], $match, $filterSetting['filter']);
        }
//        $this->reportPolicy('request');
        return [$this->preventLogRequestEnvelope(), $this->censorRequest];
    }


    /**
     * @return bool[]
     */
    public function processResponse(Response $request): array
    {
        foreach ($this->config->getFilterSettings() as $filterSetting) {
            if (!in_array($filterSetting['aspect'], self::RESPONSE_ASPECTS)) {
                continue;
            }
            $aspectValue = $this->extractAspectFromResponse($request, $filterSetting['aspect']);
            $match = $this->aspectMatchesCondition($aspectValue, $filterSetting['condition'], $filterSetting['value']);
            $this->updatePolicy($match, $filterSetting['filter']);
//            $this->reportMatch('response', $aspectValue, $filterSetting['condition'], $filterSetting['value'], $match, $filterSetting['filter']);
        }
//        $this->reportPolicy('response');
        return [$this->preventLogResponseEnvelope(), $this->censorResponse];
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


    private function preventLogRequestEnvelope(): bool
    {
        return $this->forbidRequest
            || $this->requiredForRequestFailed
            || $this->allowsRequest === false;
    }


    private function preventLogResponseEnvelope(): bool
    {
        return $this->forbidResponse
            || $this->requiredForResponseFailed
            || $this->allowsResponse === false;
    }


    private function reportPolicy(string $stage): void
    {
        $this->logger->info("POLICY @ $stage", [
            '$forbidRequest' => $this->forbidRequest,
            '$forbidResponse' => $this->forbidResponse,
            '$censorRequest' => $this->censorRequest,
            '$censorResponse' => $this->censorResponse,
            '$requiredForRequestFailed' => $this->requiredForRequestFailed,
            '$requiredForResponseFailed' => $this->requiredForResponseFailed,
            '$allowsRequest' => $this->allowsRequest,
            '$allowsResponse' => $this->allowsResponse,
            'preventLogRequestEnvelope()' => $this->preventLogRequestEnvelope(),
            'preventLogResponseEnvelope()' => $this->preventLogResponseEnvelope()
        ]);
    }

    private function reportMatch(
        string $stage,
        string $aspectValue,
        string $condition,
        string $conditionValue,
        bool   $match,
        string $filter
    ): void
    {
        $this->logger->info("Matching @ $stage", [
            '$aspectValue' => strtolower($aspectValue),
            '$condition' => $condition,
            '$conditionValue' => strtolower($conditionValue),
            '$match' => $match,
            '$filter' => $filter
        ]);
    }


}
