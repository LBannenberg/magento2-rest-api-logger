<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class RequestConsequence extends Select
{
    /**
     * @var array{array{value: string, label: string}}
     */
    protected $_options = [ // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
        ['value' => 'allow_both', 'label' => 'Can log request & can log response'],
        ['value' => 'allow_request', 'label' => 'Can log request'],
        ['value' => 'allow_response', 'label' => 'Can log response'],
        ['value' => 'forbid_both', 'label' => 'Never log request & Never log response'],
        ['value' => 'forbid_request', 'label' => 'Never log request'],
        ['value' => 'forbid_response', 'label' => 'Never log response'],
        ['value' => 'censor_both', 'label' => 'Censor request body & censor response body'],
        ['value' => 'censor_request', 'label' => 'Censor request body'],
        ['value' => 'censor_response', 'label' => 'Censor response body'],
        ['value' => 'require_both', 'label' => 'Required to log request & required to log response'],
        ['value' => 'require_request', 'label' => 'Required to log request'],
        ['value' => 'require_response', 'label' => 'Required to log response'],
    ];

    public function setInputName(string $value): self
    {
        return $this->setName($value);
    }
}
