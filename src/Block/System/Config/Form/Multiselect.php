<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\System\Config\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Multiselect extends \Magento\Config\Block\System\Config\Form\Field
{
    //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    protected function _getElementHtml(AbstractElement $element)
    {
        return parent::_getElementHtml($element) . "
        <script>
            require([
                'jquery',
                'chosen',
                'domReady!'
            ], function ($, chosen) {
                $('#" . $element->getId() . "').chosen({
                    width: '100%',
                    placeholder_text: '" . __('Select Options') . "'
                });
            })
        </script>
        ";
    }
}
