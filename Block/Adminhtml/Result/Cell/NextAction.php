<?php

namespace Klevu\Troubleshoot\Block\Adminhtml\Result\Cell;

use Klevu\Troubleshoot\Block\Adminhtml\Result\Cell;

/**
 * Class NextAction
 * @package Klevu\Troubleshoot\Block\Adminhtml\Result\Cell
 */
class NextAction extends Cell
{
    /**
     * @param $value
     * @param null $notSyncable
     * @return $this|NextAction
     */
    public function setTableCell($value, $notSyncable = null)
    {
        $this->setNotSyncable($notSyncable);
        $this->prepareLabel($value);
        $this->prepareClassName($value);
        
        return $this;
    }

    /**
     * Prepare Label
     *
     * @param $value
     */
    public function prepareLabel($value)
    {
        $this->setLabel($this->getNextActionLabel($value));
    }

    /**
     * Returns label to show
     *
     * @param $value
     * @return \Magento\Framework\Phrase
     */
    private function getNextActionLabel($value)
    {
        return $this->getNotSyncable() || ($this->getRowMissingCheck() && $value === 'NONE') ? __('NONE') : $value;
    }

    /**
     * Prepare classname and tooltip content
     *
     * @param $value
     */
    public function prepareClassName($value)
    {
        $className = strtoupper($this->getLabel()) === 'NONE' ? 'success' : 'warning';

        $content = __('Klevu has determined there is no action needed on this product, it is up to date.');
        if ($className === 'warning') {
            $content = __('Klevu has determined that this product needs to be sent to Klevu on the next sync.');
        }
        $this->prepareContent($content);
        $this->setClassName($className);
    }
}
