<?php

namespace Klevu\Troubleshoot\Block\Adminhtml\Result;

use Klevu\Troubleshoot\Block\Adminhtml\TroubleshootResult;

/**
 * Class Cell
 * @package Klevu\Troubleshoot\Block\Adminhtml\Result
 */
class Cell extends TroubleshootResult
{
    /**
     * Setting template
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('Klevu_Troubleshoot::result/row/cell.phtml');
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTableCell($value)
    {
        $this->prepareLabel($value);
        $this->prepareClassName($value);
        return $this;
    }

    /**
     * @param $value
     */
    public function prepareLabel($value)
    {
        $this->setLabel($value);
    }

    /**
     * @param $value
     */
    public function prepareClassName($value)
    {
        $class = !empty($value) ? 'success' : 'warning';
        $this->setClassName($class);
    }

    /**
     * @param $value
     */
    public function prepareContent($value)
    {
        $this->setContent($value);
    }
}
