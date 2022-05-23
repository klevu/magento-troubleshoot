<?php

namespace Klevu\Troubleshoot\Block\Adminhtml;

use Klevu\Search\Helper\Config as KlevuConfig;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class TroubleshootForm
 * @package Klevu\Troubleshoot\Block\Adminhtml
 */
class TroubleshootForm extends Template
{
    const FORM_ACTION_URL = "klevu_troubleshoot/troubleshoot/post";

    /**
     * @var KlevuConfig
     */
    private $_searchHelperConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * TroubleshootForm constructor.
     * @param Context $context
     * @param KlevuConfig $searchHelperConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        KlevuConfig $searchHelperConfig,
        array $data = []
    ) {
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_storeManager = $context->getStoreManager();
        parent::__construct($context, $data);
    }

    /**
     * Returns form action URL
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl(self::FORM_ACTION_URL);
    }

    /**
     * Returns only Klevu configured stores
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getStoreSelectData()
    {
        $data = [];
        $stores = $this->_storeManager->getStores(false);
        foreach ($stores as $store) {
            /** @var StoreInterface $store */
            if (
                !$this->_searchHelperConfig->getJsApiKey($store)
                || !$this->_searchHelperConfig->getRestApiKey($store)
            ) {
                // Skipping non-configured stores
                continue;
            }

            $website = $store->getWebsite()->getName();
            $group = $store->getGroup()->getName();

            if (!isset($data[$website])) {
                $data[$website] = [];
            }
            if (!isset($data[$website][$group])) {
                $data[$website][$group] = [];
            }

            $data[$website][$group][] = $store;
        }
        return $data;
    }
}
