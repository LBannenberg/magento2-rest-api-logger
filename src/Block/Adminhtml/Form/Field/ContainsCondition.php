<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class ContainsCondition extends Select
{
    /**
     * @var array{array{value: string, label: string}}
     */
    protected $_options = [ // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
        ['value' => 'contains', 'label' => 'contains'],
        ['value' => 'does not contain', 'label' => 'does not contain'],
        ['value' => '=', 'label' => 'is exactly']
    ];

    public function setInputName(string $value): self
    {
        return $this->setName($value);
    }
}
