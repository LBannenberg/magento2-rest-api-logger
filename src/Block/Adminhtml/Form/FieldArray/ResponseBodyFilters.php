<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\Adminhtml\Form\FieldArray;

use Corrivate\RestApiLogger\Block\Adminhtml\Form\Field\ContainsCondition;
use Corrivate\RestApiLogger\Block\Adminhtml\Form\Field\ResponseConsequence;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\BlockInterface;

class ResponseBodyFilters extends AbstractFieldArray
{
    /** @var BlockInterface */
    private $conditionRender;

    /** @var BlockInterface */
    private $consequenceRenderer;


    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    protected function _prepareToRender()
    {
        $this->addColumn('condition', ['label' => __('Condition'), 'renderer' => $this->getConditionRenderer()]);

        $this->addColumn('value', ['label' => (string)__('Text in response body')]);

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

        $condition = $row->getCondition();
        if ($condition !== null) {
            /** @phpstan-ignore-next-line method present on concrete class, not interface :( */
            $options['option_' . $this->getConditionRenderer()->calcOptionHash($condition)] = 'selected="selected"';
        }

        $consequence = $row->getConsequence();
        if ($consequence !== null) {
            /** @phpstan-ignore-next-line method present on concrete class, not interface :( */
            $options['option_' . $this->getConsequenceRenderer()->calcOptionHash($consequence)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }


    private function getConditionRenderer(): BlockInterface
    {
        if (!$this->conditionRender) {/** @phpstan-ignore-line */
            $this->conditionRender = $this->getLayout()->createBlock(
                ContainsCondition::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->conditionRender;
    }


    private function getConsequenceRenderer(): BlockInterface
    {
        if (!$this->consequenceRenderer) { /** @phpstan-ignore-line */
            $this->consequenceRenderer = $this->getLayout()->createBlock(
                ResponseConsequence::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->consequenceRenderer;
    }
}
