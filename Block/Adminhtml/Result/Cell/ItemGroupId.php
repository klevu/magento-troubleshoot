<?php

namespace Klevu\Troubleshoot\Block\Adminhtml\Result\Cell;

use Klevu\Troubleshoot\Block\Adminhtml\Result\Cell;

class ItemGroupId extends Cell
{
    /**
     * @param $value
     * @param null $product_id
     * @return $this|ItemGroupId
     */
    public function setTableCell($value, $product_id = null)
    {
        $this->prepareLabel($value);
        $this->setMainProductId($product_id);
        $this->prepareClassName($value);

        return $this;
    }

    /**
     * Prepare classname and tooltip content
     *
     * @param $value
     */
    public function prepareClassName($value)
    {
        $className = $this->getCellClassName($value);
        if (empty($value)) {
            $className = 'warning';
        }
        if ((string)$this->getMainProductId() !== (string)$value) {
            $content = 'The Klevu ID is a combination of the Magento Parent ID and Product ID for this product.';
        } else {
            $content = 'The Klevu ID is the same as the Magento Product ID for this product.';
        }
        $this->prepareContent($content);
        $this->setClassName($className);
    }
}
