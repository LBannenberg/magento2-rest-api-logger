<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\Webapi\Rest\Request;

class HttpMethod extends Select
{
    /**
     * @var array{array{value: string, label: string}}
     */
    protected $_options = [ // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
        ['value' => Request::HTTP_METHOD_GET, 'label' => Request::HTTP_METHOD_GET],
        ['value' => Request::HTTP_METHOD_DELETE, 'label' => Request::HTTP_METHOD_DELETE],
        ['value' => Request::HTTP_METHOD_PUT, 'label' => Request::HTTP_METHOD_PUT],
        ['value' => Request::HTTP_METHOD_POST, 'label' => Request::HTTP_METHOD_POST]
    ];

    public function setInputName(string $value): self
    {
        return $this->setName($value);
    }
}
