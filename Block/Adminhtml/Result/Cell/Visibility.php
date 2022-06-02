<?php

namespace Klevu\Troubleshoot\Block\Adminhtml\Result\Cell;

use Klevu\Troubleshoot\Block\Adminhtml\Result\Cell;

/**
 * Class Visibility
 * @package Klevu\Troubleshoot\Block\Adminhtml\Result\Cell
 */
class Visibility extends Cell
{
    /**
     * Prepare label
     *
     * @param $value
     */
    public function prepareLabel($value)
    {
        $label = $this->getVisibilityLabel($value);
        if (strtolower($label) === 'missing') {
            $this->setRowMissingCheck($value);
        }
        $this->setLabel($label);
    }

    /**
     * Returns label
     *
     * @param $value
     * @return \Magento\Framework\Phrase
     */
    private function getVisibilityLabel($value)
    {
        if (empty($value)) {
            return __('Missing');
        }
        return $value;
    }

    /**
     * Prepare classname and tooltip content
     *
     * @param $value
     */
    public function prepareClassName($value)
    {
        $preValueText = $value;
        $isMissing = strtolower($this->getVisibilityLabel($value)) === 'missing';
        $className = is_int($value) || $isMissing ? 'warning' : 'success';
        $flag = strtolower($value) !== 'missing' && is_int($value) && !($value >= 1 && $value <= 4);
        $content = '';

        if ($className === 'success') {
            $content = __('This product has a valid Visibility value so will be included in the sync.');
        } elseif ($className === 'warning' || $flag) {
            $content = __('Invalid data found for Visibility: %1, so this product will not be included in the sync.', $value);
        }

        if (!$isMissing && $preValueText == 'Not Visible Individually') {
            $content = __('This product has a valid Visibility value, but is not visible individually so will not be included in the sync.');
            $className = 'warning';
        }

        if (!$this->isCatalogVisibilityEnabled() && $preValueText == 'Catalog') {
            $className = 'warning';
            $content = __('The setting \'<strong>Include Visibility: Catalog</strong>\' is disabled in the Klevu settings of Magento store configuration, so this product will not be included in the sync.');
        }
        $this->prepareContent($content);
        $this->setClassName($className);
    }
}
