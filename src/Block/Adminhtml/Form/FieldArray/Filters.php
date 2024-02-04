<?php declare(strict_types=1);

namespace Corrivate\RestApiLogger\Block\Adminhtml\Form\FieldArray;

use Corrivate\RestApiLogger\Block\Adminhtml\Form\Field\Aspect;
use Corrivate\RestApiLogger\Block\Adminhtml\Form\Field\Condition;
use Corrivate\RestApiLogger\Block\Adminhtml\Form\Field\Filter;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\BlockInterface;


class Filters extends AbstractFieldArray
{
    /** @var BlockInterface */
    private $aspectRenderer;

    /** @var BlockInterface */
    private $conditionRender;

    /** @var BlockInterface */
    private $filterRenderer;


    protected function _prepareToRender()
    {
        $this->addColumn('aspect', ['label' => __('Aspect'), 'renderer' => $this->getAspectRenderer()]);
        $this->addColumn('condition', ['label' => __('Condition'), 'renderer' => $this->getConditionRenderer()]);
        $this->addColumn('value', ['label' => __('Value'), 'class' => 'required-entry']);
        $this->addColumn('filter', ['label' => __('Filter'), 'renderer' => $this->getFilterRenderer()]);

        $this->_addAfter = false;
        $this->_addButtonLabel = (string)__('Add Filter');
    }

    public function _prepareArrayRow(DataObject $row)
    {
        $options = [];

        $aspect = $row->getAspect();
        if($aspect !== null) {
            /** @phpstan-ignore-next-line method present on concrete class, not interface :( */
            $options['option_' . $this->getAspectRenderer()->calcOptionHash($aspect)] = 'selected="selected"';
        }

        $condition = $row->getCondition();
        if($condition !== null) {
            /** @phpstan-ignore-next-line method present on concrete class, not interface :( */
            $options['option_' . $this->getConditionRenderer()->calcOptionHash($condition)] = 'selected="selected"';
        }

        $filter = $row->getFilter();
        if($filter !== null) {
            /** @phpstan-ignore-next-line method present on concrete class, not interface :( */
            $options['option_' . $this->getFilterRenderer()->calcOptionHash($filter)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }


    private function getAspectRenderer(): BlockInterface
    {
        if(!$this->aspectRenderer) {/** @phpstan-ignore-line */
            $this->aspectRenderer = $this->getLayout()->createBlock(
                Aspect::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->aspectRenderer;
    }

    private function getConditionRenderer(): BlockInterface
    {
        if(!$this->conditionRender) {/** @phpstan-ignore-line */
            $this->conditionRender = $this->getLayout()->createBlock(
                Condition::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->conditionRender;
    }


    private function getFilterRenderer(): BlockInterface
    {
        if(!$this->filterRenderer) {/** @phpstan-ignore-line */
            $this->filterRenderer = $this->getLayout()->createBlock(
                Filter::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->filterRenderer;
    }
}
