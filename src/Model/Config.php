<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Model;

use Corrivate\RestApiLogger\Model\Config\Filter;
use Corrivate\RestApiLogger\Traits\ConfigTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    use ConfigTrait;

    private const BASE_PATH = 'corrivate_rest_api_logger/';
    public const REQUEST_ASPECTS = ['method', 'route', 'user_agent', 'ip', 'request_body', 'endpoint'];
    public const RESPONSE_ASPECTS = ['status_code', 'response_body'];

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function enabled(): bool
    {
        return $this->getBool(self::BASE_PATH . 'general/enabled');
    }


    public function saferMode(): bool
    {
        return $this->getBool(self::BASE_PATH . 'general/safer_mode');
    }


    public function includeHeaders(): bool
    {
        return $this->getBool(self::BASE_PATH . 'general/include_headers')
            && !$this->saferMode();
    }


    /**
     * @return Filter[]
     */
    public function getRequestFilters(): array
    {
        return array_filter(
            $this->getAllFilters(),
            fn($f) => in_array($f->aspect, self::REQUEST_ASPECTS)
        );
    }


    /**
     * @return Filter[]
     */
    public function getResponseFilters(): array
    {
        return array_filter(
            $this->getAllFilters(),
            fn($f) => in_array($f->aspect, self::RESPONSE_ASPECTS)
        );
    }


    /**
     * @return Filter[]
     */
    private function getAllFilters(): array
    {
        return array_merge(
            $this->saferModeFilters(),
            $this->getMethodFilters(),
            $this->getEndpointFilters(),
            $this->getCustomFilters(),
        );
    }


    /** @return Filter[] */
    private function saferModeFilters(): array
    {
        if (!$this->saferMode()) {
            return [];
        }
        return [
            new Filter('request_body', 'contains', 'street', 'censor_both'),
            new Filter('response_body', 'contains', 'street', 'censor_response')
        ];
    }


    /** @return Filter[] */
    private function getMethodFilters(): array
    {
        $result = [];
        foreach ($this->getDynamicRows(self::BASE_PATH . 'filters/methods') as $filter) {
            $result[] = new Filter(
                'method',
                '=',
                $filter['value'],
                $filter['consequence'],
                $this->getTagsFromFilter($filter)
            );
        }
        return $result;
    }


    /** @return Filter[] */
    private function getEndpointFilters(): array
    {
        $result = [];
        foreach ($this->getDynamicRows(self::BASE_PATH . 'filters/endpoints') as $filter) {
            $result[] = new Filter(
                'endpoint',
                '=',
                $filter['value'],
                $filter['consequence'],
                $this->getTagsFromFilter($filter)
            );
        }
        return $result;
    }



    /** @return Filter[] */
    private function getCustomFilters(): array
    {
        $result = [];
        foreach ($this->getDynamicRows(self::BASE_PATH . 'filters/filter_rows') as $filter) {
            $result[] = new Filter(
                $filter['aspect'],
                $filter['condition'],
                $filter['value'],
                $filter['consequence'],
                $this->getTagsFromFilter($filter)
            );
        }
        return $result;
    }


    /** @return string[] */
    public function getExcludedServices(): array
    {
        return $this->getMultiselectStrings(self::BASE_PATH . 'services/exclude_services');
    }


    /** @return string[] */
    public function getIncludedServices(): array
    {
        return $this->getMultiselectStrings(self::BASE_PATH . 'services/include_services');
    }


    /**
     * @param string[] $filter
     * @return string[]
     */
    private function getTagsFromFilter(array $filter): array
    {
        return array_filter(
            array_map(
                fn($tag) => trim($tag),
                explode(',', $filter['tags'] ?? '')
            )
        );
    }
}
