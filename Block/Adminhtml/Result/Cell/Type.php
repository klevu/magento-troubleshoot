<?php

namespace Klevu\Troubleshoot\Block\Adminhtml\Result\Cell;

use Klevu\Troubleshoot\Block\Adminhtml\Result\Cell;

/**
 * Class Type
 * @package Klevu\Troubleshoot\Block\Adminhtml\Result\Cell
 */
class Type extends Cell
{
    /**
     * Prepare label
     *
     * @param $value
     */
    public function prepareLabel($value)
    {
        $label = $this->getCellAttLabel($value);
        if (strtolower($label) === 'missing') {
            $this->setRowMissingCheck($value);
        }
        $this->setLabel(ucfirst($label));
    }

    /**
     * Prepare classname and tooltip content
     *
     * @param $value
     */
    public function prepareClassName($value)
    {
        $className = $this->getCellClassName($value);
        if (!empty($value) && !$this->isProductTypeIdAllowedForSync($value)) {
            $className = 'warning';
            $content = __('Klevu does not support <strong>%1</strong> product type by default.', $value);
        } else {
            $content = __('Klevu supports <strong>%1</strong> product type by default.', $value);
        }
        $this->prepareContent($content);
        $this->setClassName($className);
    }
}
