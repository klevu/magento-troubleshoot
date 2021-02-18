<?php


namespace Klevu\Troubleshoot\Model\Actions;


use Klevu\Search\Model\Klevu\KlevuFactory as Klevu_Factory;
use Klevu\Search\Model\Product\ProductParentInterface as Klevu_Product_Parent;
use Klevu\Troubleshoot\Model\Actions\Common as Klevu_CommonAction;
use Klevu\Troubleshoot\Model\TroubleshootContext as Klevu_TroubleshootContext;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as Magento_CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel as AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Class Delete
 * @package Klevu\Troubleshoot\Model\Actions
 */
class Delete extends AbstractModel
{
    /**
     * @var Klevu_Factory
     */
    private $_klevuFactory;

    /**
     * @var Magento_CollectionFactory
     */
    private $_magentoCollectionFactory;

    /**
     * @var ResourceConnection
     */
    private $_frameworkModelResource;

    /**
     * @var
     */
    private $_klevuConfig;

    /**
     * @var ProductIndividualInterface
     */
    private $_klevuProductIndividualInterface;

    /**
     * @var Klevu_Product_Parent
     */
    private $_klevuProductParentInterface;

    /**
     * @var Common
     */
    private $_klevuCommonAction;

    /**
     * @var
     */
    private $_storeModelStoreManagerInterface;

    /**
     * Delete constructor.
     * @param Context $mcontext
     * @param Klevu_TroubleshootContext $context
     * @param Magento_CollectionFactory $magentoCollectionFactory
     * @param Klevu_Factory $klevuFactory
     * @param Common $klevuCommonAction
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $mcontext,
        Klevu_TroubleshootContext $context,
        Magento_CollectionFactory $magentoCollectionFactory,
        Klevu_Factory $klevuFactory,
        Klevu_CommonAction $klevuCommonAction,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {

        parent::__construct($mcontext, $registry, $resource, $resourceCollection, $data);
        $this->_storeModelStoreManagerInterface = $context->getStoreManagerInterface();
        $this->_klevuFactory = $klevuFactory;
        $this->_magentoCollectionFactory = $magentoCollectionFactory;
        $this->_klevuCommonAction = $klevuCommonAction;
        $this->_klevuConfig = $context->getHelperManager()->getConfigHelper();
        $this->_klevuProductIndividualInterface = $context->getKlevuProductIndividual();
        $this->_klevuProductParentInterface = $context->getKlevuProductParent();
        $this->_frameworkModelResource = $context->getResourceConnection();

    }

    /**
     * Returns ids which are expected for delete operaton
     *
     * @param $store
     * @return array
     *
     * Source: \Klevu\Search\Model\Product\MagentoProductActions::deleteProductCollection
     */
    public function getQueueIds($store)
    {
        $products_ids_delete = array();
        // Get 'simple','bundle','grouped','Virtual','downloadable' which have parent and visibility search,both
        $productCollection = $this->_magentoCollectionFactory->create()
            ->addFieldToSelect('id');

        $productCollection->addStoreFilter($store->getId());
        $productCollection->addAttributeToFilter('type_id', array('in' => $this->_klevuProductIndividualInterface->getProductIndividualTypeArray()));
        $productCollection->addAttributeToFilter('status', array('eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
        //set visibility filter
        if ($this->_klevuConfig->useCatalogVisibitySync($store->getId())) {
            $productCollection->addAttributeToFilter('visibility', array('in' => array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG)));
        } else {
            $productCollection->addAttributeToFilter('visibility', array('in' => array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)));
        }
        $m_product_ids = array();
        $delChildIDs = array();
        foreach ($productCollection->getData() as $key => $value) {
            $delChildIDs[] = $value['entity_id'];
        }

        $parent_ids = $this->_klevuCommonAction->getParentRelationsByChild($delChildIDs);
        foreach ($productCollection->getData() as $key => $value) {
            if (isset($parent_ids[$value['entity_id']]) == true) {
                foreach ($parent_ids[$value['entity_id']] as $pkey => $pvalue) {
                    $m_product_ids[] = $pvalue['entity_id'] . "-" . $value['entity_id'];
                }
            }
            $parent_id = 0;
            // 1 = not visible individual
            if ($value['visibility'] !== "1") {
                $m_product_ids[] = $parent_id . "-" . $value['entity_id'];
            }
        }
        $enable_parent_ids = array();
        // Get parent product,disabled or visibility catalog
        $productCollection = $this->_magentoCollectionFactory->create()
            ->addFieldToSelect('id');
        $productCollection->addStoreFilter($store->getId());
        $productCollection->addAttributeToFilter('type_id', array('in' => $this->_klevuProductParentInterface->getProductParentTypeArray()));
        if ($this->_klevuConfig->useCatalogVisibitySync($store->getId())) {
            $productCollection->addAttributeToFilter('visibility', array('in' => array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG)));
        } else {
            $productCollection->addAttributeToFilter('visibility', array('in' => array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH)));
        }
        $productCollection->addAttributeToFilter('status', array('eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));

        foreach ($productCollection->getData() as $key => $value) {
            $enable_parent_ids[$value['entity_id']] = $value['entity_id'];
        }

        $k_product_ids = array();
        $klevuCollection = $this->_klevuCommonAction->getKlevuProductCollection($store);
        foreach ($klevuCollection as $k_key => $k_value) {
            $k_product_ids[] = $k_value['parent_id'] . "-" . $k_value['product_id'];
            if ($k_value['parent_id'] !== "0") {
                if (isset($enable_parent_ids[$k_value['parent_id']]) == false) {
                    $products_ids_delete[$k_value['product_id']]['parent_id'] = $k_value['parent_id'];
                    $products_ids_delete[$k_value['product_id']]['product_id'] = $k_value['product_id'];

                }
            }
        }
        $products_to_delete = array_diff($k_product_ids, $m_product_ids);
        foreach ($products_to_delete as $key => $value) {
            $ids = explode('-', $value);
            $products_ids_delete[$value]['parent_id'] = $ids[0];
            $products_ids_delete[$value]['product_id'] = $ids[1];
        }
        //have to apply unique to remove duplicate deletion
        $products_ids_delete = array_unique($products_ids_delete, SORT_REGULAR);
        return $products_ids_delete;
    }


}
