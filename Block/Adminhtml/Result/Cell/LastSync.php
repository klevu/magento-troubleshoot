<?php

namespace Klevu\Troubleshoot\Block\Adminhtml\Result\Cell;

use Klevu\Troubleshoot\Block\Adminhtml\Result\Cell;

/**
 * Class LastSync
 * @package Klevu\Troubleshoot\Block\Adminhtml\Result\Cell
 */
class LastSync extends Cell
{
    /**
     * @param $value
     * @param null $updatedAt
     * @param null $notSyncable
     * @return $this|LastSync
     */
    public function setTableCell($value, $updatedAt = null, $notSyncable = null)
    {
        $this->setUpdatedAt($updatedAt);
        $this->setNotSyncable($notSyncable);
        $this->prepareLabel($value);
        $this->prepareClassName($value);

        return $this;
    }

    /**
     * Prepare label
     *
     * @param $value
     */
    public function prepareLabel($value)
    {
        $label = empty($value) || $this->getNotSyncable() ? __('Not Found') : $value;
        $this->setLabel($label);
    }

    /**
     * Prepare classname and tooltip content
     *
     * @param $value
     */
    public function prepareClassName($value)
    {
        $className = $value !== '0000-00-00 00:00:00' ? 'success' : 'warning';
        $timezoneComment = __('This value comes from your database so the timezone may be different.');

        if (empty($value)) {
            $className = 'warning';
            $content = __('This product was not found in the klevu_product_sync table.');
        } elseif ($value !== 'Not Found' && strtotime($this->getUpdatedAt()) > strtotime($value)) {
            $content = __('This Product was modified since the last sync with Klevu, so you should see it flagged as Add, Update or Delete.');
            $content .= ' ' . $timezoneComment;
            $className = 'warning';
        } elseif ($value !== 'Not Found' && strtotime($this->getUpdatedAt()) <= strtotime($value)) {
            $content = __('This Product was not modified since the last sync with Klevu, so there should be no next Klevu action.');
            $content .= ' ' . $timezoneComment;
            $className = 'success';
        } else {
            $content = $timezoneComment;
        }

        /* wont show last sync data if product is notSyncable and if all three flags are missing on STORE LEVEL */
        //$className = $value == 'NONE' ? __('notapply') : $className;
        $this->prepareContent($content);
        $this->setClassName($className);
    }
}
