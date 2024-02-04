<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class Filter extends Select
{
    /**
     * @var array{array{value: string, label: string}}
     */
    protected $_options = [ // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
        ['value' => 'forbid_both', 'label' => 'forbid both'],
        ['value' => 'forbid_request', 'label' => 'forbid request'],
        ['value' => 'forbid_response', 'label' => 'forbid response'],
        ['value' => 'censor_both', 'label' => 'censor both'],
        ['value' => 'censor_request', 'label' => 'censor request'],
        ['value' => 'censor_response', 'label' => 'censor response'],
        ['value' => 'require_both', 'label' => 'required for both'],
        ['value' => 'require_request', 'label' => 'required for request'],
        ['value' => 'require_response', 'label' => 'required for response'],
        ['value' => 'allow_both', 'label' => 'allow both'],
        ['value' => 'allow_request', 'label' => 'allow request'],
        ['value' => 'allow_response', 'label' => 'allow response'],
    ];

    public function setInputName(string $value): self
    {
        return $this->setName($value);
    }
}
