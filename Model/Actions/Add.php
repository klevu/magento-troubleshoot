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
 * Class Add
 * @package Klevu\Troubleshoot\Model\Actions
 */
class Add extends AbstractModel
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
     * Add constructor.
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

        $this->_klevuFactory = $klevuFactory;
        $this->_magentoCollectionFactory = $magentoCollectionFactory;
        $this->_klevuCommonAction = $klevuCommonAction;
        $this->_klevuConfig = $context->getHelperManager()->getConfigHelper();
        $this->_klevuProductIndividualInterface = $context->getKlevuProductIndividual();
        $this->_klevuProductParentInterface = $context->getKlevuProductParent();
    }

    /**
     * Returns ids which are expected for add operaton
     *
     * @param $store
     * @return array
     *
     * Source: \Klevu\Search\Model\Product\MagentoProductActions::addProductCollection
     */
    public function getQueueIds($store)
    {
        $products_ids_add = array();
        // Get 'simple','bundle','grouped','Virtual','downloadable' which dont have parent and visibility search,both
        // Use factory to create a new product collection
        $productCollection = $this->_magentoCollectionFactory->create()
            ->addFieldToSelect('id');
        $productCollection->addStoreFilter($store->getId());
        $productCollection->addAttributeToFilter('type_id', array('in' => $this->_klevuProductIndividualInterface->getProductIndividualTypeArray()));
        $productCollection->addAttributeToFilter('status', array('eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
        //set visibility filter
        if ($this->_klevuConfig->useCatalogVisibitySync($store->getId())) {
            $productCollection->addAttributeToFilter('visibility', array('in' => array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG)));
        } else {
            $productCollection->addAttributeToFilter('visibility', array('in' => array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH)));
        }

        $m_product_ids = array();
        $childIds = array();
        foreach ($productCollection->getData() as $key => $value) {
            $childIds[] = $value['entity_id'];
        }

        $parent_ids_all = $this->_klevuCommonAction->getParentRelationsByChild($childIds);
        foreach ($productCollection->getData() as $key => $value) {
            if (isset($parent_ids_all[$value['entity_id']]) == true) {
                foreach ($parent_ids_all[$value['entity_id']] as $pkey => $pvalue) {
                    $m_product_ids[] = $pvalue['entity_id'] . "-" . $value['entity_id'];
                }
            }
            $parent_id = 0;
            $m_product_ids[] = $parent_id . "-" . $value['entity_id'];
        }

        // Get Simple product which have parent and visibility not visible individual
        $productCollection = $this->_magentoCollectionFactory->create()
            ->addFieldToSelect('id');
        $productCollection->addStoreFilter($store->getId());
        $productCollection->addAttributeToFilter('type_id', array('in' => $this->_klevuProductIndividualInterface->getProductChildTypeArray()));
        $productCollection->addAttributeToFilter('status', array('eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
        //set visibility filter
        $productCollection->addAttributeToFilter('visibility', array('in' => array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)));

        $notVisibleChildIDs = array();
        foreach ($productCollection->getData() as $key => $value) {
            $notVisibleChildIDs[] = $value['entity_id'];
        }
        $parent_ids = $this->_klevuCommonAction->getParentRelationsByChild($notVisibleChildIDs);
        foreach ($productCollection->getData() as $key => $value) {
            if (isset($parent_ids[$value['entity_id']]) == true) {
                foreach ($parent_ids[$value['entity_id']] as $pkey => $pvalue) {
                    $m_product_ids[] = $pvalue['entity_id'] . "-" . $value['entity_id'];
                }
            }
        }

        $enable_parent_ids = array();
        // Get parent product,enabled or visibility catalogsearch,search
        $productCollection = $this->_magentoCollectionFactory->create()
            ->addFieldToSelect('id');
        $productCollection->addStoreFilter($store->getId());
        $productCollection->addAttributeToFilter('type_id', array('in' => $this->_klevuProductParentInterface->getProductParentTypeArray()));
        if ($this->_klevuConfig->useCatalogVisibitySync($store->getId())) {
            $productCollection->addAttributeToFilter('visibility', array('in' => array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG)));
        } else {
            $productCollection->addAttributeToFilter('visibility', array('in' => array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH)));
        }
        // disable not working for flat indexing  so checking for enabled product only
        $productCollection->addAttributeToFilter('status', array('eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
        foreach ($productCollection->getData() as $key => $value) {
            $enable_parent_ids[$value['entity_id']] = $value['entity_id'];
        }

        $k_product_ids = array();
        $klevuCollection = $this->_klevuCommonAction->getKlevuProductCollection($store);
        foreach ($klevuCollection as $k_key => $k_value) {
            $k_product_ids[] = $k_value['parent_id'] . "-" . $k_value['product_id'];
        }

        $products_to_add = array_diff($m_product_ids, $k_product_ids);

        foreach ($products_to_add as $key => $value) {
            $ids = explode('-', $value);
            if ($ids[0] !== "0") {
                if (isset($enable_parent_ids[$ids[0]]) == true) {
                    $products_ids_add[$value]['parent_id'] = $ids[0];
                    $products_ids_add[$value]['product_id'] = $ids[1];
                }
            } else {
                $products_ids_add[$value]['parent_id'] = $ids[0];
                $products_ids_add[$value]['product_id'] = $ids[1];
            }
        }
        return $products_ids_add;
    }

}
