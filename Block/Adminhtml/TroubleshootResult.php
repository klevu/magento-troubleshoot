<?php

namespace Klevu\Troubleshoot\Block\Adminhtml;

use Klevu\Search\Model\Product\ProductIndividualInterface as KlevuSearch_ProductIndividual;
use Klevu\Troubleshoot\Model\Troubleshoot as TroubleshootModel;
use Klevu\Troubleshoot\Model\TroubleshootActions;
use Klevu\Troubleshoot\Model\TroubleshootLoadAttribute;
use Magento\Backend\Block\Template;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/**
 * Class TroubleshootResult
 * @package Klevu\Troubleshoot\Block\Adminhtml
 */
class TroubleshootResult extends Template
{
    private $product;

    private $troubleshootModel;

    private $troubleshootProductAction;

    private $troubleshootLoadAttribute;

    private $searchProductIndividual;

    private $outOfStockFlag = false;

    /**
     * TroubleshootResult constructor.
     * @param Template\Context $context
     * @param KlevuSearch_ProductIndividual $searchProductIndividual
     * @param TroubleshootModel $troubleshootModel
     * @param TroubleshootLoadAttribute $troubleshootLoadAttribute
     * @param TroubleshootActions $troubleshootProductAction
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        KlevuSearch_ProductIndividual $searchProductIndividual,
        TroubleshootModel $troubleshootModel,
        TroubleshootLoadAttribute $troubleshootLoadAttribute,
        TroubleshootActions $troubleshootProductAction,
        array $data = []
    )
    {
        $this->searchProductIndividual = $searchProductIndividual;
        $this->troubleshootModel = $troubleshootModel;
        $this->troubleshootLoadAttribute = $troubleshootLoadAttribute;
        $this->troubleshootProductAction = $troubleshootProductAction;
        parent::__construct($context, $data);
    }

    /**
     * Catalog product load
     *
     * @return false|ProductInterface|string[]|null
     */
    public function getCatalogProduct()
    {
        try {
            $this->product = $this->troubleshootModel->loadCatalogProduct($this->getStoreId(), $this->getProductId());
            if (!$this->product instanceof ProductInterface) {
                throw new \Exception('Requested product not found or invalid');
            }
        } catch (\Exception $e) {
            $this->product = false;
        }
        return $this->product;
    }

    /**
     * Checks product type is configurable or not
     *
     * @return bool
     */
    public function isConfigurable()
    {
        return strtolower($this->product->getTypeId()) === Configurable::TYPE_CODE;
    }

    /**
     * Returns parent product information
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getParentInfo()
    {
        return $this->troubleshootModel->getParentInfo();
    }

    /**
     * Checks whether product is available in catalog_product_indexer_price table or not
     *
     * @return bool
     */
    public function isExistsInCatalogProductIndexPrice()
    {
        return (int)$this->troubleshootModel->isExistsInCatalogProductIndexPrice($this->getProductId());
    }


    /**
     * Returns update_at from catalog_product_entity
     *
     * @return false
     */
    public function getLastUpdatedAt()
    {
        return $this->troubleshootModel->getLastUpdatedAt($this->getProductId());
    }

    /**
     * Check if collection method is enable or not
     *
     * @return bool
     */
    public function getCollectionMethodStatus()
    {
        return $this->troubleshootModel->isCollectionEnabled();
    }

    /**
     * Checks whether product is loadable via collection or not
     *
     * @return bool
     */
    public function getProductLoadableStatus()
    {
        return (int)$this->troubleshootLoadAttribute->isProductLoadableViaCollection($this->getProductId(), $this->getStoreId());
    }

    /**
     * Checks whether product type id is supported by Klevu or not
     *
     * @param string $type_id
     * @return bool
     */
    public function isProductTypeIdAllowedForSync($type_id)
    {
        if ($type_id == Configurable::TYPE_CODE) {
            return true;
        }

        //checking only individual types only
        $types = $this->searchProductIndividual->getProductIndividualTypeArray();
        if (in_array($type_id, $types)) {
            return true;
        }
        return false;
    }

    /**
     * Returns classname
     *
     * @param $value
     * @return string
     */
    public function getCellClassName($value)
    {
        if ($this->outOfStockFlag) {
            $this->outOfStockFlag = false;
            return 'warning';
        }
        return !empty($value) ? 'success' : 'warning';
    }

    /**
     * Returns label name
     *
     * @param $value
     * @return \Magento\Framework\Phrase
     */
    public function getCellAttLabel($value)
    {
        //checking only null
        if (is_null($value)) {
            return __('Missing');
        }
        return is_int($value) ? $this->getYesNoFlag($value) : $value;
    }

    /**
     * Returns yes or no
     *
     * @param $value
     * @return \Magento\Framework\Phrase
     */
    public function getYesNoFlag($value)
    {
        return $value ? __('Yes') : __('No');
    }

    /**
     * Loads template
     *
     * @param $data
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTableHeadRowHtml($data)
    {
        return $this->getLayout()->createBlock(\Magento\Backend\Block\Template::class)
            ->setTemplate('Klevu_Troubleshoot::result/row/head.phtml')
            ->addData($data)
            ->toHtml();
    }

    /**
     * Get "display out of stock" setting text
     *
     * @param $indexPrice
     * @return string|void
     */
    public function getCatalogProductIndexPriceText($indexPrice)
    {
        //wont show if index_price has rows or status is disabled
        if ($indexPrice || (int)$this->product->getStatus() === 2) {
            return;
        }

        //No for 'Display Out of Stock Products' AND 'Stock Status' is OOS then only
        if (!$this->getDisplayOutofstockStatus() && !$this->getProductStockStatus()) {
            $this->outOfStockFlag = true;
            return " (out of stock)";
        } else {
            return;
        }
    }

    /**
     * Check if "display out of stock" setting is set to "No" or "yes"
     *
     * @return bool
     */
    public function getDisplayOutofstockStatus()
    {
        return $this->troubleshootModel->isDisplayOutofstock();
    }

    /**
     * Returns product stock status
     *
     * @param $product_id
     * @param null $store_id
     * @return int|void
     */
    public function getProductStockStatus()
    {
        return $this->troubleshootModel->getProductStockStatus();
    }

    /**
     * Returns catalog visibility flag
     *
     * @return bool
     */
    public function isCatalogVisibilityEnabled()
    {
        return $this->troubleshootModel->getCatalogVisibilityStatus();
    }
}
