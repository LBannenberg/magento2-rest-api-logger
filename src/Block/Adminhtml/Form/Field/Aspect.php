<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class Aspect extends Select
{
    /**
     * @var array{array{value: string, label: string}}
     */
    protected $_options = [ // phpcs:ignore
        ['value' => 'method', 'label' => 'HTTP method'],
        ['value' => 'route', 'label' => 'route'],
        ['value' => 'status_code', 'label' => 'HTTP status code'],
        ['value' => 'ip', 'label' => 'IP address'],
        ['value' => 'user_agent', 'label' => 'user agent'],
        ['value' => 'request_body', 'label' => 'request body'],
        ['value' => 'response_body', 'label' => 'response body']
    ];

    public function setInputName(string $value): self
    {
        return $this->setName($value);
    }
}
