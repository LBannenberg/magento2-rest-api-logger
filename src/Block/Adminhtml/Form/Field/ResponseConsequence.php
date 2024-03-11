<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class ResponseConsequence extends Select
{
    /**
     * @var array{array{value: string, label: string}}
     */
    protected $_options = [ // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
        ['value' => 'allow_response', 'label' => 'Can log response'],
        ['value' => 'forbid_response', 'label' => 'Never log response'],
        ['value' => 'censor_response', 'label' => 'Censor response body'],
        ['value' => 'require_response', 'label' => 'Required to log response'],
    ];

    public function setInputName(string $value): self
    {
        return $this->setName($value);
    }
}
