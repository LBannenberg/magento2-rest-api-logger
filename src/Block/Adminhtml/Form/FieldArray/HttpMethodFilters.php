<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\Adminhtml\Form\FieldArray;

use Corrivate\RestApiLogger\Block\Adminhtml\Form\Field\Consequence;
use Corrivate\RestApiLogger\Block\Adminhtml\Form\Field\HttpMethods;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\BlockInterface;

class HttpMethodFilters extends AbstractFieldArray
{
    /** @var BlockInterface */
    private $httpMethodRenderer;

    /** @var BlockInterface */
    private $consequenceRenderer;


    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    protected function _prepareToRender()
    {
        $this->addColumn('aspect', [
            'label' => (string)__('HTTP Method'), 'renderer' => $this->getHttpMethodRenderer()
        ]);
        $this->addColumn('consequence', [
            'label' => (string)__('Consequence'), 'renderer' => $this->getConsequenceRenderer()
        ]);
        $this->addColumn('tags', ['label' => (string)__('Tags')]);

        $this->_addAfter = false;
        $this->_addButtonLabel = (string)__('Add Filter');
    }

    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    public function _prepareArrayRow(DataObject $row)
    {
        $options = [];

        $aspect = $row->getAspect();
        if ($aspect !== null) {
            /** @phpstan-ignore-next-line method present on concrete class, not interface :( */
            $options['option_' . $this->getHttpMethodRenderer()->calcOptionHash($aspect)] = 'selected="selected"';
        }

        $consequence = $row->getConsequence();
        if ($consequence !== null) {
            /** @phpstan-ignore-next-line method present on concrete class, not interface :( */
            $options['option_' . $this->getConsequenceRenderer()->calcOptionHash($consequence)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }


    private function getHttpMethodRenderer(): BlockInterface
    {
        if (!$this->httpMethodRenderer) { /** @phpstan-ignore-line */
            $this->httpMethodRenderer = $this->getLayout()->createBlock(
                HttpMethods::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->httpMethodRenderer;
    }


    private function getConsequenceRenderer(): BlockInterface
    {
        if (!$this->consequenceRenderer) { /** @phpstan-ignore-line */
            $this->consequenceRenderer = $this->getLayout()->createBlock(
                Consequence::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->consequenceRenderer;
    }
}
