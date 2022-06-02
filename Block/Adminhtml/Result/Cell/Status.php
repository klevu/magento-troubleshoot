<?php

namespace Klevu\Troubleshoot\Block\Adminhtml\Result\Cell;

use Klevu\Troubleshoot\Block\Adminhtml\Result\Cell;

/**
 * Class Status
 * @package Klevu\Troubleshoot\Block\Adminhtml\Result\Cell
 */
class Status extends Cell
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
        $this->setLabel($label);
    }

    /**
     * Prepare classname and tooltip content
     *
     * @param $value
     */
    public function prepareClassName($value)
    {
        $className = strtolower($value) === 'enabled' ? 'success' : 'warning';
        $statusFlag = strtolower($value) !== 'missing' && is_int($value) && !($value >= 1 && $value <= 2);
        $content = '';
        if ($className === 'success') {
            $content = __('This product is Enabled so will be included in the sync.');
        } elseif ($className === 'warning' || $statusFlag) {
            $content = __('This product is Disabled or Invalid so will not be included in the sync.');
        }
        $this->prepareContent($content);
        $this->setClassName($className);
    }
}
