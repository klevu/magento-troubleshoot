<?php


namespace Klevu\Troubleshoot\Model\Actions;


use Klevu\Search\Model\Klevu\KlevuFactory as Klevu_Factory;
use Klevu\Troubleshoot\Model\TroubleshootContext as Klevu_TroubleshootContext;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as Magento_CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel as AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Class Update
 * @package Klevu\Troubleshoot\Model\Actions
 */
class Update extends AbstractModel
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
     * Update constructor.
     * @param Context $mcontext
     * @param Klevu_Factory $klevuFactory
     * @param Klevu_TroubleshootContext $context
     * @param Magento_CollectionFactory $magentoCollectionFactory
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $mcontext,
        Klevu_Factory $klevuFactory,
        Klevu_TroubleshootContext $context,
        Magento_CollectionFactory $magentoCollectionFactory,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {

        parent::__construct($mcontext, $registry, $resource, $resourceCollection, $data);

        $this->_klevuFactory = $klevuFactory;
        $this->_magentoCollectionFactory = $magentoCollectionFactory;
        $this->_frameworkModelResource = $context->getResourceConnection();
    }

    /**
     * Returns ids which are expected for update operaton
     *
     * @param $store
     * @return array
     *
     * Source: \Klevu\Search\Model\Product\MagentoProductActions::updateProductCollection
     */
    public function getQueueIds($store)
    {
        $klevu = $this->_klevuFactory->create();
        $klevuCollection = $klevu->getCollection()
            ->addFieldToFilter($klevu->getKlevuField('type'), $klevu->getKlevuType('product'))
            ->addFieldToFilter($klevu->getKlevuField('store_id'), $store->getId())
            ->join(
                ['product' => $this->_frameworkModelResource->getTableName('catalog_product_entity')],
                "main_table." . $klevu->getKlevuField('product_id') . " = product.entity_id AND product.updated_at > main_table.last_synced_at",
                ""
            );
        $klevuCollection->load();
        $klevuToUpdate = array();
        if ($klevuCollection->count() > 0) {
            foreach ($klevuCollection as $klevuItem) {
                $parentFieldId = $klevuItem->getData($klevu->getKlevuField('parent_id'));
                $productFieldId = $klevuItem->getData($klevu->getKlevuField('product_id'));
                $uniqueGroupKey = $parentFieldId . "-" . $productFieldId;
                $klevuToUpdate[$uniqueGroupKey]["product_id"] = $productFieldId;
                $klevuToUpdate[$uniqueGroupKey]["parent_id"] = $parentFieldId;
            }
        }
        return $klevuToUpdate;
    }


}
