<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Model\Config\Source;

use Magento\Framework\Webapi\Rest\Request;

class HttpMethods implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @param mixed $isMultiselect
     * @return array<array{'value': string, 'label': string}>
     */
    public function toOptionArray($isMultiselect = false): array
    {
        $options = [
            ['value' => Request::HTTP_METHOD_GET, 'label' => Request::HTTP_METHOD_GET],
            ['value' => Request::HTTP_METHOD_DELETE, 'label' => Request::HTTP_METHOD_DELETE],
            ['value' => Request::HTTP_METHOD_PUT, 'label' => Request::HTTP_METHOD_PUT],
            ['value' => Request::HTTP_METHOD_POST, 'label' => Request::HTTP_METHOD_POST]
        ];
        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => (string)__('--Please Select--')]);
        }
        return $options;
    }
}
