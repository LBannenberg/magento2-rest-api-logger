<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class ComparisonCondition extends Select
{
    /**
     * @var array{array{value: string, label: string}}
     */
    protected $_options = [ // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
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
