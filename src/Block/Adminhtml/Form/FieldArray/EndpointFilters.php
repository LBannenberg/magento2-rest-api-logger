<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\Adminhtml\Form\FieldArray;

use Corrivate\RestApiLogger\Block\Adminhtml\Form\Field\Consequence;
use Corrivate\RestApiLogger\Block\Adminhtml\Form\Field\Endpoint;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\BlockInterface;

class EndpointFilters extends AbstractFieldArray
{
    /** @var BlockInterface */
    private $endpointRenderer;

    /** @var BlockInterface */
    private $consequenceRenderer;


    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    protected function _prepareToRender()
    {
        $this->addColumn('value', [
            'label' => (string)__('Endpoint'), 'renderer' => $this->getEndpointRenderer()
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
            $options['option_' . $this->getEndpointRenderer()->calcOptionHash($aspect)] = 'selected="selected"';
        }

        $consequence = $row->getConsequence();
        if ($consequence !== null) {
            /** @phpstan-ignore-next-line method present on concrete class, not interface :( */
            $options['option_' . $this->getConsequenceRenderer()->calcOptionHash($consequence)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }


    private function getEndpointRenderer(): BlockInterface
    {
        if (!$this->endpointRenderer) { /** @phpstan-ignore-line */
            $this->endpointRenderer = $this->getLayout()->createBlock(
                Endpoint::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->endpointRenderer;
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
