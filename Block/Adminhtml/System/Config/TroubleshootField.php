<?php

namespace Klevu\Troubleshoot\Block\Adminhtml\System\Config;

use Klevu\Troubleshoot\Block\Adminhtml\TroubleshootForm;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class TroubleshootField
 * @package Klevu\Troubleshoot\Block\Adminhtml\System\Config
 */
class TroubleshootField extends Fieldset
{
    /**
     * Renders troubleshoot form
     *
     * @param AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(AbstractElement $element)
    {
        return $this->getLayout()->createBlock(
            TroubleshootForm::class,
            'klevu_troubleshootform'
        )->setTemplate(
            'Klevu_Troubleshoot::troubleshoot-form.phtml'
        )->toHtml();
    }
}
