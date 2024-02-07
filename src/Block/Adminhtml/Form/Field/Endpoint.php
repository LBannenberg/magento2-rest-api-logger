<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\Adminhtml\Form\Field;

use Corrivate\RestApiLogger\Model\Config\Source\Services;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

class Endpoint extends Select
{
    public function __construct( /** @phpstan-ignore-line */
        Context $context,
        Services $services,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setOptions($services->toOptionArray());
    }


    public function setInputName(string $value): self
    {
        return $this->setName($value);
    }
}
