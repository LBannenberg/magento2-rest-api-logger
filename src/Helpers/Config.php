<?php

namespace Corrivate\RestApiLogger\Helpers;

use Corrivate\RestApiLogger\Traits\ConfigTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    use ConfigTrait;

    const BASE_PATH = 'corrivate_rest_api_logger/';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function enabled(): bool
    {
        return $this->getBool(self::BASE_PATH . 'general/enabled');
    }


    public function includeHeaders(): bool
    {
        return $this->getBool(self::BASE_PATH . 'general/include_headers');
    }


    /**
     * @return string[]
     */
    public function logRequestMethods(): array
    {
        return array_merge(
            $this->getMultiselectStrings(self::BASE_PATH . 'methods/request_title'),
            $this->logRequestMethodBody()
        );
    }


    /**
     * @return string[]
     */
    public function logRequestMethodBody(): array
    {
        return $this->getMultiselectStrings(self::BASE_PATH . 'methods/request_body');
    }


    /**
     * @return string[]
     */
    public function logResponseMethods(): array
    {
        return array_merge(
            $this->getMultiselectStrings(self::BASE_PATH . 'methods/response_title'),
            $this->logResponseMethodBody()
        );
    }


    /**
     * @return string[]
     */
    public function logResponseMethodBody(): array
    {
        return $this->getMultiselectStrings(self::BASE_PATH . 'methods/response_body');
    }


    /**
     * @return array<string,array<string, string>>
     */
    public function getFilterSettings(): array
    {
        return $this->getDynamicRows(self::BASE_PATH . 'filters/filter_rows');
    }

}
