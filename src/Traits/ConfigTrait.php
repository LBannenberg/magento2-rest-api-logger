<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Traits;

use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

trait ConfigTrait
{
    private function getValue(string $path, int $storeId = Store::DEFAULT_STORE_ID): string
    {
        if ($storeId) {
            return (string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        }
        return (string)$this->scopeConfig->getValue($path);
    }

    private function getBool(string $path, int $storeId = Store::DEFAULT_STORE_ID): bool
    {
        return (bool)$this->getValue($path, $storeId);
    }


    /**
     * @return string[]
     */
    private function getMultiselectStrings(string $path, int $storeId = Store::DEFAULT_STORE_ID): array
    {
        $value = $this->getValue($path, $storeId);
        $values = explode(',', $value);
        // remove trailing whitespace and empty strings
        return array_filter(array_map(fn($item) => trim($item), $values));
    }


    /**
     * @return array<string,array<string, string>>
     */
    private function getDynamicRows(string $configPath, int $storeId = Store::DEFAULT_STORE_ID): array
    {
        $value = $storeId
            ? $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $storeId)
            : $this->scopeConfig->getValue($configPath);

        if (!$value) {
            return [];
        }

        if (is_array($value)) { // this happens when you use a config.xml to preload the settings
            return $value;
        }

        $result = json_decode($value, true);
        if (!$result) {
            return [];
        }

        return $result;
    }
}
