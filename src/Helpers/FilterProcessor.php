<?php

namespace Corrivate\RestApiLogger\Helpers;

class FilterProcessor
{
    private Config $config;


    public function __construct(
        Config $config
    )
    {
        $this->config = $config;
    }


    public function process(Aspects $aspects, Policy $policy, bool $isResponse): Policy
    {
        foreach ($this->config->getFilterSettings() as $filterRow) {
            $aspect = $filterRow['aspect'];
            if (in_array($aspect, ['response_body', 'status_code']) && !$isResponse) {
                continue;
            }

            $match = $this->aspectMatchesCondition(
                $aspects->$aspect,
                $filterRow['condition'],
                $filterRow['value']
            );

            $policy = $this->updatePolicy($match, $filterRow['filter'], $policy);
        }
        return $policy;
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


    private function updatePolicy(bool $match, string $filter, Policy $policy): Policy
    {
        if ($match) {
            switch ($filter) {
                case 'forbid_both':
                    $policy->forbidRequest = true;
                    $policy->forbidResponse = true;
                    return $policy;
                case 'forbid_request':
                    $policy->forbidRequest = true;
                    return $policy;
                case 'forbid_response':
                    $policy->forbidResponse = true;
                    return $policy;
                case 'censor_both':
                    $policy->censorRequest = true;
                    $policy->censorResponse = true;
                    return $policy;
                case 'censor_request':
                    $policy->censorRequest = true;
                    return $policy;
                case 'censor_response':
                    $policy->censorResponse = true;
                    return $policy;

                // A single allow condition is enough to toggle this to success
                case 'allow_both':
                    $policy->allowsRequest = true;
                    $policy->allowsResponse = true;
                    return $policy;
                case 'allow_request':
                    $policy->allowsRequest = true;
                    return $policy;
                case 'allow_response':
                    $policy->allowsResponse = true;
                    return $policy;
            }
        }


        switch ($filter) {
            // A single failed require is enough to toggle this to failure
            case 'require_both':
                $policy->requiredForRequestFailed = true;
                $policy->requiredForResponseFailed = true;
                return $policy;
            case 'require_request':
                $policy->requiredForRequestFailed = true;
                return $policy;
            case 'require_response':
                $policy->requiredForResponseFailed = true;
                return $policy;

            // If null, set to failed, but don't overwrite success
            case 'allow_both':
                $policy->allowsRequest ??= false;
                $policy->allowsResponse ??= false;
                return $policy;
            case 'allow_request':
                $policy->allowsRequest ??= false;
                return $policy;
            case 'allow_response':
                $policy->allowsResponse ??= false;
                return $policy;
        }
        return $policy;
    }
}
