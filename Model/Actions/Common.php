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
 * Class Common
 * @package Klevu\Troubleshoot\Model\Actions
 */
class Common extends AbstractModel
{
    /**
     * @var Klevu_Factory
     */
    private $_klevuFactory;

    /**
     * @var ResourceConnection
     */
    private $_frameworkModelResource;

    /**
     * @var Magento_OptionProvider
     */
    private $_magentoOptionProvider;

    /**
     * MagentoProductActions constructor.
     * @param Context $mcontext
     * @param Klevu_TroubleshootContext $context
     * @param Magento_CollectionFactory $magentoCollectionFactory
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $mcontext,
        Klevu_TroubleshootContext $context,
        Klevu_Factory $klevuFactory,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($mcontext, $registry, $resource, $resourceCollection, $data);
        $this->_klevuFactory = $klevuFactory;
        $this->_frameworkModelResource = $context->getResourceConnection();
        $this->_magentoOptionProvider= $context->getMagentoOptionProvider();
    }

    /**
     * Returns parent ids for child id
     *
     * @param $ids
     * @return mixed
     *
     * Source: \Klevu\Search\Model\Product\MagentoProductActions::getParentRelationsByChild
     */
    public function getParentRelationsByChild($ids)
    {
        /** @var \Magento\Framework\DB\Adapter\Pdo\Mysql $connection */
        $connection = $this->_frameworkModelResource->getConnection();

        /** @var \Magento\Framework\Db\Select $select */
        $select = $connection
            ->select()
            ->from(['l' => $this->_frameworkModelResource->getTableName('catalog_product_super_link')], [])
            ->join(
                ['e' => $this->_frameworkModelResource->getTableName('catalog_product_entity')],
                'e.'. $this->_magentoOptionProvider->getProductEntityLinkField() . ' = l.parent_id',
                ['e.entity_id']
            )->where('l.product_id IN(?)', $ids);
        $select->reset('columns');
        $select->columns(array('e.entity_id', 'l.product_id', 'l.parent_id'));

        $result = [];
        $list = $this->_frameworkModelResource->getConnection()->fetchAll($select);
        if (empty($list)) {
            return $result;
        }
        foreach ($list as $row) {
            if (isset($result[$row['product_id']]) == false) {
                $result[$row['product_id']] = [];
            }
            $result[$row['product_id']][] = $row;
        }
        return $result;
    }

    /**
     * Get product collection from klevu product sync
     *
     * @param $store
     * @return AbstractDb |
     * \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection|
     *  null
     *
     * Source: \Klevu\Search\Model\Product\MagentoProductActions::getKlevuProductCollection
     */
    public function getKlevuProductCollection($store)
    {
        $klevu = $this->_klevuFactory->create();
        $klevuCollection = $klevu->getCollection()
            ->addFieldToSelect($klevu->getKlevuField('product_id'))
            ->addFieldToSelect($klevu->getKlevuField('parent_id'))
            ->addFieldToSelect($klevu->getKlevuField('store_id'))
            ->addFieldToFilter($klevu->getKlevuField('type'), $klevu->getKlevuType('product'))
            ->addFieldToFilter($klevu->getKlevuField('store_id'), $store->getId());

        return $klevuCollection;
    }


}
