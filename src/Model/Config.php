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
    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function enabled(): bool
    {
        return $this->getBool(self::BASE_PATH . 'general/enabled');
    }


    public function saferModeEnabled(): bool
    {
        return $this->getBool(self::BASE_PATH . 'general/safer_mode');
    }


    /** @return Filter[] */
    private function saferModeRequestFilters(): array
    {
        if (!$this->saferModeEnabled()) {
            return [];
        }
        return [
            new Filter('request_body', 'contains', 'street', 'censor_both'),

            // Endpoints likely to contain sensitive data
            // Filtered as routes so we don't have to decompose them on every request
            new Filter('route', 'contains', '/V1/applepay', 'censor_both'),
            new Filter('route', 'contains', '/V1/braintree', 'censor_both'),
            new Filter('route', 'contains', '/V1/carts', 'censor_both'),
            new Filter('route', 'contains', '/V1/creditmemo', 'censor_both'),
            new Filter('route', 'contains', '/V1/customers', 'censor_both'),
            new Filter('route', 'contains', '/V1/guest-carts', 'censor_both'),
            new Filter('route', 'contains', '/V1/inventory/get-latlng-from-address', 'censor_both'),
            new Filter('route', 'contains', '/V1/get-latslngs-from-address', 'censor_both'),
            new Filter('route', 'contains', '/V1/invoices', 'censor_both'),
            new Filter('route', 'contains', '/V1/orders', 'censor_both'),
            new Filter('route', 'contains', '/V1/shipment', 'censor_both'),
            new Filter('route', 'contains', '/V1/tfa', 'censor_both'),
        ];
    }

    /** @return Filter[] */
    private function saferModeResponseFilters(): array
    {
        if (!$this->saferModeEnabled()) {
            return [];
        }
        return [
            new Filter('response_body', 'contains', 'street', 'censor_response')
        ];
    }


    public function includeHeaders(): bool
    {
        return $this->getBool(self::BASE_PATH . 'general/include_headers')
            && !$this->saferModeEnabled();
    }


    /**
     * @return Filter[]
     */
    public function getRequestFilters(): array
    {
        return array_merge(
            $this->saferModeRequestFilters(),
            $this->mapRowsToFilters('request_filters/method', 'method'),
            $this->mapRowsToFilters('request_filters/endpoint', 'endpoint'),
            $this->mapRowsToFilters('request_filters/route', 'route'),
            $this->mapRowsToFilters('request_filters/ip_address', 'ip_address'),
            $this->mapRowsToFilters('request_filters/user_agent', 'user_agent'),
            $this->mapRowsToFilters('request_filters/request_body', 'request_body')
        );
    }


    /**
     * @return Filter[]
     */
    public function getResponseFilters(): array
    {
        return array_merge(
            $this->saferModeResponseFilters(),
            $this->mapRowsToFilters('response_filters/status_code', 'status_code'),
            $this->mapRowsToFilters('response_filters/response_body', 'response_body')
        );
    }


    /** @return Filter[] */
    private function mapRowsToFilters(string $path, string $aspect): array
    {
        $rows = $this->getDynamicRows(self::BASE_PATH . $path);
        return array_map(
            fn($row) => new Filter(
                $aspect,
                $row['condition'] ?? '=',
                $row['value'],
                $row['consequence'],
                $this->unpackTagsFromRow($row)
            ),
            $rows
        );
    }


    /**
     * @param string[] $filter
     * @return string[]
     */
    private function unpackTagsFromRow(array $filter): array
    {
        return array_filter(
            array_map(
                fn($tag) => trim($tag),
                explode(',', $filter['tags'] ?? '')
            )
        );
    }
}
