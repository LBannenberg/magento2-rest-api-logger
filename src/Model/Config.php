<?php declare(strict_types=1);

namespace Corrivate\RestApiLogger\Model;

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


    public function saferMode(): bool
    {
        return $this->getBool(self::BASE_PATH . 'general/safer_mode');
    }

    /**
     * @return array<string,array<string, string>>
     */
    private function saferModeFilters(): array
    {
        return [
            "safer_mode_request_body_contains_street" => [
                "aspect" => "request_body",
                "condition" => "contains",
                "value" => "street",
                "filter" => "censor_both"],
            "safer_mode_response_body_contains_street" => [
                "aspect" => "response_body",
                "condition" => "contains",
                "value" => "street",
                "filter" => "censor_response"]
        ];
    }


    public function includeHeaders(): bool
    {
        return $this->getBool(self::BASE_PATH . 'general/include_headers')
            && !$this->saferMode();
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
        $filterSettings = $this->getDynamicRows(self::BASE_PATH . 'filters/filter_rows');
        if ($this->saferMode()) {
            $filterSettings = array_merge($filterSettings, $this->saferModeFilters());
        }
        return $filterSettings;
    }


    /**
     * @return string[]
     */
    public function getExcludedServices(): array
    {
        return $this->getMultiselectStrings(self::BASE_PATH . 'services/exclude_services');
    }


    /**
     * @return string[]
     */
    public function getIncludedServices(): array
    {
        return $this->getMultiselectStrings(self::BASE_PATH . 'services/include_services');
    }

}
