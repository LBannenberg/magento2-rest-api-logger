<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Model\Config\Source;

class Services implements \Magento\Framework\Data\OptionSourceInterface
{
    protected \Magento\Webapi\Model\Config $config;


    /**
     * @var array<string,array<string, string>>
     */
    protected array $options;
    public function __construct(
        \Magento\Webapi\Model\Config $config
    ) {
        $this->config = $config;
    }


    /**
     * @return array<array{'label': string, 'value': array<array{'label': string, 'value': string}>}>
     */
    public function toOptionArray(): array
    {
        return $this->getOptGroupsArray($this->getOptions());
    }


    /**
     * @return array<string,array<string, string>>
     */
    private function getOptions(): array
    {
        if (!isset($this->options)) {
            $this->options = [];
            $options = $this->config->getServices()[\Magento\Webapi\Model\Config\Converter::KEY_ROUTES];
            foreach ($options as $route => $methods) {
                $group = trim(ucwords(preg_replace('/^\/([^\/]+)\/([^\/]+).*/', '$1 $2', $route)));

                foreach ($methods as $method => $details) {
                    $service = $method . ' ' . trim($route, '/');
                    $this->options[$group][$service] = $service;
                }
            }

            ksort($this->options);

        }

        return $this->options;
    }


    /**
     * @param array<string, string> $options
     * @return array<array{'label': string, 'value':string}>
     */
    private function getOptionsArray(array $options): array
    {
        $optionArray = [];
        foreach ($options as $label => $value) {
            $optionArray[] = [
                'label' => $label,
                'value' => $value,
            ];
        }
        return $optionArray;
    }


    /**
     * @param array<string,array<string, string>> $groups
     * @return array<array{'label': string, 'value': array<array{'label': string, 'value': string}>}>
     */
    private function getOptGroupsArray(array $groups): array
    {
        $optGroups = [];
        foreach ($groups as $label => $options) {
            $optGroups[] = [
                'label' => $label,
                'value' => $this->getOptionsArray($options)
            ];
        }
        return $optGroups;
    }
}
