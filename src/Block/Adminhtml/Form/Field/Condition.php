<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class Condition extends Select
{
    /**
     * @var array{array{value: string, label: string}}
     */
    protected $_options = [
        ['value' => 'contains', 'label' => 'contains'],
        ['value' => 'does not contain', 'label' => 'does not contain'],
        ['value' => '=', 'label' => '='],
        ['value' => '!=', 'label' => '!='],
        ['value' => '>=', 'label' => '>='],
        ['value' => '>', 'label' => '>'],
        ['value' => '<=', 'label' => '<='],
        ['value' => '<', 'label' => '<']
    ];

    public function setInputName(string $value): self
    {
        return $this->setName($value);
    }
}
