<?php

namespace Corrivate\RestApiLogger\Model\Config\Source;

class NoYesIUnderstand implements \Magento\Framework\Data\OptionSourceInterface
{

    /**
     * @return array<array{'value': int, 'label': string}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 1, 'label' => __('Yes (I understand the risks)')],
            ['value' => 0, 'label' => __('No')]
        ];
    }
}
