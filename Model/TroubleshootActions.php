<?php


namespace Klevu\Troubleshoot\Model;


use Klevu\Troubleshoot\Model\Actions\Add as Klevu_AddAction;
use Klevu\Troubleshoot\Model\Actions\Common as Klevu_CommonAction;
use Klevu\Troubleshoot\Model\Actions\Delete as Klevu_DeleteAction;
use Klevu\Troubleshoot\Model\Actions\Update as Klevu_UpdateAction;
use Klevu\Troubleshoot\Model\TroubleshootContext as Klevu_TroubleshootContext;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as Magento_CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel as AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Class TroubleshootActions
 * @package Klevu\Troubleshoot\Model
 */
class TroubleshootActions extends AbstractModel
{
    private $_storeModelStoreManagerInterface;
    /**
     * @var Magento_CollectionFactory
     */
    private $_magentoCollectionFactory;
    /**
     * @var Klevu_UpdateAction
     */
    private $klevuUpdateAction;
    /**
     * @var Klevu_CommonAction
     */
    private $klevuCommonAction;
    /**
     * @var Klevu_DeleteAction
     */
    private $klevuDeleteAction;
    /**
     * @var Klevu_AddAction
     */
    private $klevuAddAction;


    /**
     * TroubleshootActions constructor.
     * @param Context $mcontext
     * @param TroubleshootContext $context
     * @param Klevu_AddAction $klevuAddAction
     * @param Klevu_UpdateAction $klevuUpdateAction
     * @param Klevu_DeleteAction $klevuDeleteAction
     * @param Klevu_CommonAction $klevuCommonAction
     * @param Magento_CollectionFactory $magentoCollectionFactory
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $mcontext,
        Klevu_TroubleshootContext $context,
        Klevu_AddAction $klevuAddAction,
        Klevu_UpdateAction $klevuUpdateAction,
        Klevu_DeleteAction $klevuDeleteAction,
        Klevu_CommonAction $klevuCommonAction,
        Magento_CollectionFactory $magentoCollectionFactory,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {

        parent::__construct($mcontext, $registry, $resource, $resourceCollection, $data);
        $this->_storeModelStoreManagerInterface = $context->getStoreManagerInterface();
        $this->_magentoCollectionFactory = $magentoCollectionFactory;
        $this->klevuAddAction = $klevuAddAction;
        $this->klevuUpdateAction = $klevuUpdateAction;
        $this->klevuDeleteAction = $klevuDeleteAction;
        $this->klevuCommonAction = $klevuCommonAction;

    }

    /**
     * Returns requested store object
     *
     * @param int $store_id
     * @return mixed
     */
    private function getStoreObject($store_id)
    {
        return $this->_storeModelStoreManagerInterface->getStore($store_id);
    }

    /**
     * Returns sync related operation data
     *
     * @param $store_id
     * @param $parent_ids_to_check
     * @param $product_id_to_check
     * @return array
     */
    public function getNextQueueIds($store_id, $parent_ids_to_check, $product_id_to_check)
    {
        $response = array();
        $storeObject = $this->getStoreObject($store_id);

        $deleteIds = $this->klevuDeleteAction->getQueueIds($storeObject);
        $response['delete'] = $this->getIdsExistsInQueue($deleteIds, $parent_ids_to_check, $product_id_to_check);

        $updateIds = $this->klevuUpdateAction->getQueueIds($storeObject);
        $response['update'] = $this->getIdsExistsInQueue($updateIds, $parent_ids_to_check, $product_id_to_check);

        $addIds = $this->klevuAddAction->getQueueIds($storeObject);
        $response['add'] = $this->getIdsExistsInQueue($addIds, $parent_ids_to_check, $product_id_to_check);
        return $response;
    }


    /**
     * Check whether ids exist in collection or not
     *
     * @param $collection_ids
     * @param $parent_ids
     * @param $product_id
     * @return array
     */
    public function getIdsExistsInQueue($collection_ids, $parent_ids, $product_id)
    {
        $queueIds = array();
        foreach ($collection_ids as $key => $row) {
            $row_prepare = $row['parent_id'] . "-" . $row['product_id'];
            //parentid-childid i.e.(0-124)
            if ($row['parent_id'] == 0 && $product_id == $row['product_id']) {
                $queueIds[$product_id] = $product_id;
            } elseif (!empty($parent_ids)) {
                foreach ($parent_ids as $parent_id) {
                    //parentid-childid i.e.(122-123)
                    $itemGroupId = $parent_id . "-" . $product_id;
                    if ($itemGroupId === $key) {
                        $queueIds[$parent_id] = $parent_id;
                    }
                }
            }
        }
        return $queueIds;
    }

    /**
     * Returns the product values for given attributes through collection
     *
     * @param $attributes
     * @param $product_id
     * @param $store_id
     * @return array
     */
    public function getProductAttrDataUsingCollection($attributes, $product_id, $store_id)
    {
        $productAttrData = array();
        $store = $this->getStoreObject($store_id);
        $productCollection = $this->_magentoCollectionFactory->create();
        /**
         * addAttributeToSelect can optionally accpet jointype but here
         * used inner join to only get relevant data
         */
        $productCollection->addAttributeToSelect($attributes, 'INNER');
        $productCollection->addStoreFilter($store->getId());
        $productCollection->addIdFilter($product_id);
        $productCollection->getSelect()->limit(1);
        $productData = $productCollection->getData();

        if (count($productCollection) >= 0) {
            foreach ($attributes as $attribute) {
                if (isset($productData[0][$attribute])) {
                    $productAttrData[$attribute] = $productData[0][$attribute];
                }
            }
        }
        return $productAttrData;
    }


}
