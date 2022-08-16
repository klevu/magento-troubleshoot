<?php

namespace Klevu\Troubleshoot\Model;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Product\ProductParentInterface as Klevu_ProductParentInterface;
use Klevu\Troubleshoot\Model\TroubleshootActions as Klevu_TroubleshootProductAction;
use Klevu\Troubleshoot\Model\TroubleshootContext as Klevu_TroubleshootContext;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as Magento_StatusAttribute;
use Magento\Catalog\Model\Product\Visibility as Magento_VisibilityAttribute;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

class Troubleshoot extends AbstractModel
{
    /**
     * @var ProductInterface
     */
    private $product;
    /**
     * @var int|string
     */
    private $storeId;
    /**
     * @var Klevu_ProductParentInterface
     */
    private $klevuProductParent;
    /**
     * @var TroubleshootActions
     */
    private $troubleshootProductAction;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var Magento_StatusAttribute
     */
    private $magentoStatusAttribute;
    /**
     * @var ResourceConnection
     */
    private $_frameworkModelResource;
    /**
     * @var ConfigHelper
     */
    private $_klevuConfig;
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistryInterface;
    /**
     * @var string
     */
    private $isExistsInKlevuProductSync;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Context $mcontext,
        Klevu_TroubleshootContext $context,
        Klevu_ProductParentInterface $klevuProductParent,
        Klevu_TroubleshootProductAction $troubleshootProductAction,
        ProductRepositoryInterface $productRepository,
        Magento_StatusAttribute $magentoStatusAttribute,
        StockRegistryInterface $stockRegistryInterface,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->klevuProductParent = $klevuProductParent;
        $this->troubleshootProductAction = $troubleshootProductAction;
        $this->productRepository = $productRepository;
        $this->magentoStatusAttribute = $magentoStatusAttribute;
        $this->stockRegistryInterface = $stockRegistryInterface;
        parent::__construct($mcontext, $registry, $resource, $resourceCollection, $data);
        $this->_frameworkModelResource = $context->getResourceConnection();
        $this->_klevuConfig = $context->getHelperManager()->getConfigHelper();
        $this->storeManager = $context->getStoreManagerInterface();
    }

    /**
     * Returns last updated at from catalog_product_entity
     *
     * @param $product_id
     * @return string|false
     */
    public function getLastUpdatedAt($product_id)
    {
        if (isset($this->product) && (int)$this->product->getId() === (int)$product_id) {
            return $this->product->getUpdatedAt();
        }
        return false;
    }

    /**
     * Returns whether product exists in catalog_product_index_price table
     *
     * @param $product_id
     * @return bool
     */
    public function isExistsInCatalogProductIndexPrice($product_id)
    {
        $select = $this->getCoreReadConnection()
            ->select()
            ->from(["cpei" => $this->_frameworkModelResource->getTableName("catalog_product_index_price")])
            ->where("cpei.entity_id = ?", $product_id)
            ->limitPage(0, 1);

        //By default, any product should have 4 rows(based on customer groups)
        $result = $this->getCoreReadConnection()->fetchRow($select);
        return !empty($result);
    }

    /**
     * Returns the core read connection
     *
     * @return mixed
     */
    private function getCoreReadConnection()
    {
        return $this->_frameworkModelResource->getConnection("core_read");
    }

    /**
     * Load product object
     *
     * @param $store_id
     * @param $product_id
     * @return \Magento\Catalog\Api\Data\ProductInterface|string[]
     */
    public function loadCatalogProduct($store_id, $product_id)
    {
        $this->storeId = $store_id;
        try {
            $this->product = $this->productRepository->getById($product_id, false, $store_id);
        } catch (NoSuchEntityException $e) {
            $this->product = null;
        } catch (\Exception $e) {
            $this->product = null;
        }
        return $this->product;
    }

    /**
     * Returns the table with sync related information
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getParentInfo()
    {
        $parentInfo = array();
        $product_id = $this->product->getId();
        $store_id = $this->storeId;

        $itemGroupIds = $parentIds = $this->getParentIds($product_id);
        //Showing requested product id to table as well
        $itemGroupIds['child'] = $product_id;

        $queueIds = $this->troubleshootProductAction->getNextQueueIds($store_id, $parentIds, $product_id);
        $attributes = array('type_id', 'status', 'visibility', 'updated_at');
        if (!empty($itemGroupIds)) {
            foreach ($itemGroupIds as $key => $itemGroupRow) {
                $productUpdatedAt = $statusDBValue = $visibilityDBValue = $productType = $productVisibility = $productStatus = '';
                $nextAction = $notSyncable = false;
                if ($key === 'child') {
                    $lastSyncKlevu = $this->isExistsInKlevuProductSync = $this->getKlevuLastSync($store_id, $itemGroupRow);
                } else {
                    $lastSyncKlevu = $this->getKlevuLastSync($store_id, $product_id, $itemGroupRow);
                }

                if ($this->isCollectionEnabled()) {
                    $productAttrData = $this->troubleshootProductAction->getProductAttrDataUsingCollection($attributes, $itemGroupRow, $store_id);

                    $statusDBValue = isset($productAttrData['status']) ? $productAttrData['status'] : NULL;
                    $statusText = $this->magentoStatusAttribute->getOptionText($statusDBValue);
                    $productStatus = $statusText ?: $statusDBValue;

                    $visibilityDBValue = isset($productAttrData['visibility']) ? $productAttrData['visibility'] : NULL;
                    $visibilityText = Magento_VisibilityAttribute::getOptionText($visibilityDBValue);
                    $productVisibility = $visibilityText ?: $visibilityDBValue;

                    $productType = isset($productAttrData['type_id']) ? $productAttrData['type_id'] : NULL;
                    $productUpdatedAt = isset($productAttrData['updated_at']) ? $productAttrData['updated_at'] : NULL;

                    $nextAction = $this->getNextActionType($itemGroupRow, $queueIds);

                } else {
                    $productObject = $this->getProductAttrUsingObject($store_id, $itemGroupRow);
                    if ($productObject instanceof ProductInterface) {
                        $productStatus = $productObject->getAttributeText('status');
                        $statusDBValue = $productObject->getData('status');

                        $productVisibility = $productObject->getAttributeText('visibility');
                        $visibilityDBValue = $productObject->getData('visibility');

                        $productType = $productObject->getTypeId();
                        $productUpdatedAt = $productObject->getUpdatedAt();

                        $nextAction = $this->getNextActionType($itemGroupRow, $queueIds);
                    }
                }

                //default 'NONE' only if product is not orphan
                if (empty($nextAction) && (int)$visibilityDBValue !== 1) {
                    $nextAction = 'NONE';
                }

                /**
                 * won't show any action for disabled(2) products and
                 * in case child product has multi-parents
                 */
                $reqProductStatus = strtolower($this->product->getAttributeText('status'));
                if ((empty($lastSyncKlevu) && (int)$statusDBValue === 2)
                    || ($reqProductStatus === 'disabled' && count($parentIds) > 1)
                ) {
                    $nextAction = 'NONE';
                }

                //orphan and not visible individually products
                if ($key === 'child' && (int)$visibilityDBValue === 1 && empty($nextAction)) {
                    $notSyncable = true;
                }

                $itemGroupId = '';
                if ((int)$itemGroupRow === (int)$product_id) {
                    $itemGroupId = $product_id;
                } else {
                    $itemGroupId = $itemGroupRow . '-' . $product_id;
                }

                /**
                 * When collection method is enabled AND
                 * 'Display Out of Stock Products' selected NO AND
                 * 'Stock Status' is OOS for particular product
                 *
                 *  - Product is expected to UPDATE then show DELETE
                 *  - Product is expected to ADD(not found in klevu_product_sync table) then show NONE
                 */
                if ($this->isCollectionEnabled()
                    && !$this->isDisplayOutofstock()
                    && !$this->getProductStockStatus()) {

                    if (!empty($this->isExistsInKlevuProductSync) &&
                        strtotime($productUpdatedAt) > strtotime($lastSyncKlevu)) {
                        $nextAction = 'DELETE';
                    } elseif (!$this->isExistsInKlevuProductSync) {
                        $nextAction = 'NONE';
                    }
                }
                $parentInfo[$itemGroupRow]['itemGroupId'] = $itemGroupId;
                $parentInfo[$itemGroupRow]['lastSyncKlevu'] = $lastSyncKlevu;
                $parentInfo[$itemGroupRow]['productStatus'] = $productStatus;
                $parentInfo[$itemGroupRow]['productVisibility'] = $productVisibility;
                $parentInfo[$itemGroupRow]['productType'] = $productType;
                $parentInfo[$itemGroupRow]['productUpdatedAt'] = $productUpdatedAt;
                $parentInfo[$itemGroupRow]['nextAction'] = $nextAction;
                $parentInfo[$itemGroupRow]['notSyncable'] = $notSyncable;
            }
        }

        return $parentInfo;
    }

    /**
     * Returns parent ids for given product id
     *
     * @param $product_id
     * @return string[]
     */
    private function getParentIds($product_id)
    {
        return $this->klevuProductParent->getParentIdsByChild($product_id);
    }

    /**
     * Returns the last synched date from klevu_product_sync table
     *
     * @param $store_id
     * @param $product_id
     * @param null $parent_id
     * @return false|mixed
     */
    public function getKlevuLastSync($store_id, $product_id, $parent_id = null)
    {
        $select = $this->getCoreReadConnection()
            ->select()
            ->from(["k" => $this->_frameworkModelResource->getTableName("klevu_product_sync")])
            ->where("k.store_id = ?", $store_id)
            ->where("k.product_id = ?", $product_id)
            ->where("k.type = ?", 'products');

        if (empty($parent_id)) {
            $parent_id = 0;
        }
        $select->where("k.parent_id = ?", $parent_id);
        $result = $this->getCoreReadConnection()->fetchRow($select);
        if (!isset($result['last_synced_at'])) {
            return false;
        }

        return $result['last_synced_at'];
    }

    /**
     * Check if collection method is enable or not
     *
     * @return bool
     */
    public function isCollectionEnabled()
    {
        return (bool)$this->_klevuConfig->isCollectionMethodEnabled();
    }

    /**
     * Returns action type for given product
     *
     * @param $idToCheck
     * @param $response
     * @return string
     */
    private function getNextActionType($idToCheck, $response)
    {
        //reset required here
        $nextAction = '';
        if (in_array($idToCheck, $response['delete'], true)) {
            $nextAction = 'DELETE';
        }

        //empty check required for those marked Disabled, queue for DELETE
        if (empty($nextAction) && in_array($idToCheck, $response['update'], true)) {
            $nextAction = 'UPDATE';
        }

        if (empty($nextAction) && in_array($idToCheck, $response['add'], true)) {
            $nextAction = 'ADD';
        }

        return $nextAction;
    }

    /**
     * Returns product information
     *
     * @param $store_id
     * @param $product_id
     * @return ProductInterface|void
     * @throws NoSuchEntityException
     */
    private function getProductAttrUsingObject($store_id, $product_id)
    {
        try {
            if (isset($this->product) && (int)$this->product->getId() === (int)$product_id) {
                return $this->product;
            }
            return $this->productRepository->getById($product_id, false, $store_id);
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Check if "display out of stock" setting is set to "No" or "yes"
     *
     * @return bool
     */
    public function isDisplayOutofstock()
    {
        return (bool)$this->_klevuConfig->displayOutofstock();
    }

    /**
     * Retrieve stock status for given product
     *
     * @return int|void
     */
    public function getProductStockStatus()
    {
        try {
            $store = $this->storeManager->getStore($this->storeId);
            $websiteId = $store->getWebsiteId();
            return $this->stockRegistryInterface->getProductStockStatus(
                $this->product->getId(),
                $websiteId
            );
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Checks whether store using catalog visibility or not
     *
     * @return bool
     */
    public function getCatalogVisibilityStatus()
    {
        return (bool)$this->_klevuConfig->useCatalogVisibitySync($this->storeId);
    }
}

