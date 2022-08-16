<?php

namespace Klevu\Troubleshoot\Block\Adminhtml;

use Klevu\Search\Model\Product\ProductIndividualInterface as KlevuSearch_ProductIndividual;
use Klevu\Troubleshoot\Block\Adminhtml\Result\Cell\ItemGroupId;
use Klevu\Troubleshoot\Block\Adminhtml\Result\Cell\LastSync;
use Klevu\Troubleshoot\Block\Adminhtml\Result\Cell\NextAction;
use Klevu\Troubleshoot\Block\Adminhtml\Result\Cell\Type;
use Klevu\Troubleshoot\Block\Adminhtml\Result\Cell\Visibility;
use Klevu\Troubleshoot\Model\Troubleshoot as TroubleshootModel;
use Klevu\Troubleshoot\Model\TroubleshootActions;
use Klevu\Troubleshoot\Model\TroubleshootLoadAttribute;
use Magento\Backend\Block\Template;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class TroubleshootResult
 * @package Klevu\Troubleshoot\Block\Adminhtml
 */
class TroubleshootResult extends Template
{
    /**
     * @var ProductInterface
     */
    private $product;
    /**
     * @var TroubleshootModel
     */
    private $troubleshootModel;
    /**
     * @var TroubleshootLoadAttribute
     */
    private $troubleshootLoadAttribute;
    /**
     * @var KlevuSearch_ProductIndividual
     */
    private $searchProductIndividual;
    /**
     * @var bool
     */
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
    ) {
        $this->searchProductIndividual = $searchProductIndividual;
        $this->troubleshootModel = $troubleshootModel;
        $this->troubleshootLoadAttribute = $troubleshootLoadAttribute;
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
     * @throws NoSuchEntityException
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
     * @param string $typeId
     *
     * @return bool
     */
    public function isProductTypeIdAllowedForSync($typeId)
    {
        $productTypes = $this->searchProductIndividual->getProductIndividualTypeArray();
        $productTypes[] = Configurable::TYPE_CODE;

        return in_array($typeId, $productTypes, true);
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
     * @throws LocalizedException
     */
    public function getTableHeadRowHtml($data)
    {
        $layout = $this->getLayout();
        $block = $layout->createBlock(Template::class);
        $block->setTemplate('Klevu_Troubleshoot::result/row/head.phtml');
        $block->addData($data);

        return $block->toHtml();
    }

    /**
     * Get "display out of stock" setting text
     *
     * @param $indexPrice
     * @return string
     */
    public function getCatalogProductIndexPriceText($indexPrice)
    {
        //wont show if index_price has rows or status is disabled
        if ($indexPrice || (int)$this->product->getStatus() === Status::STATUS_DISABLED) {
            return '';
        }

        //No for 'Display Out of Stock Products' AND 'Stock Status' is OOS then only
        if (!$this->getDisplayOutofstockStatus() && !$this->getProductStockStatus()) {
            $this->outOfStockFlag = true;
            return " (out of stock)";
        }

        return '';
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

    /**
     * @param string $itemGroupId
     * @param string $productId
     *
     * @return string
     * @throws LocalizedException
     */
    public function getProductIdTableCell($itemGroupId, $productId)
    {
        $layout = $this->getLayout();
        $block = $layout->createBlock(ItemGroupId::class);
        $block->setTableCell($itemGroupId, $productId);

        return $block->toHtml();
    }

    /**
     * @param string $productType
     *
     * @return string
     * @throws LocalizedException
     */
    public function getProductTypeTableCell($productType)
    {
        $layout = $this->getLayout();
        $block = $layout->createBlock(Type::class);
        $block->setTableCell($productType);

        return $block->toHtml();
    }

    /**
     * @param $status
     *
     * @return string
     * @throws LocalizedException
     */
    public function getStatusTableCell($status)
    {
        $layout = $this->getLayout();
        $block = $layout->createBlock(Result\Cell\Status::class);
        $block->setTableCell($status);

        return $block->toHtml();
    }

    /**
     * @param $visibility
     *
     * @return string
     * @throws LocalizedException
     */
    public function getVisibilityTableCell($visibility)
    {
        $layout = $this->getLayout();
        $block = $layout->createBlock(Visibility::class);
        $block->setTableCell($visibility);

        return $block->toHtml();
    }

    /**
     * @param string $lastSyncKlevu
     * @param string $productUpdatedAt
     * @param bool $notSyncable
     *
     * @return string
     * @throws LocalizedException
     */
    public function getLastSyncTableCell($lastSyncKlevu, $productUpdatedAt, $notSyncable)
    {
        $layout = $this->getLayout();
        $block = $layout->createBlock(LastSync::class);
        $block->setTableCell($lastSyncKlevu, $productUpdatedAt, $notSyncable);

        return $block->toHtml();
    }

    /**
     * @param $nextAction
     * @param $notSyncable
     *
     * @return string
     * @throws LocalizedException
     */
    public function getNextActionTableCell($nextAction, $notSyncable)
    {
        $layout = $this->getLayout();
        $block = $layout->createBlock(NextAction::class);
        $block->setTableCell($nextAction, $notSyncable);

        return $block->toHtml();
    }
}
