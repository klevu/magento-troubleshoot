<?php

namespace Klevu\Troubleshoot\Model;

use Klevu\Search\Model\Product\LoadAttributeInterface;
use Klevu\Troubleshoot\Model\TroubleshootContext as Klevu_TroubleshootContext;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as Magento_CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class TroubleshootLoadAttribute extends AbstractModel
{
    /**
     * @var LoadAttributeInterface
     */
    private $loadAttribute;

    public function __construct(
        Context $mcontext,
        Klevu_TroubleshootContext $context,
        Magento_CollectionFactory $magentoCollectionFactory,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        LoadAttributeInterface $loadAttribute = null
    ) {
        parent::__construct($mcontext, $registry, $resource, $resourceCollection, $data);
        $this->loadAttribute = $loadAttribute ?: ObjectManager::getInstance()->get(LoadAttributeInterface::class);
    }

    /**
     * Checks whether data loading using magento collection method
     *
     * @param $product_ids
     * @param null $store_id
     *
     * @return bool
     */
    public function isProductLoadableViaCollection($product_ids, $store_id = null)
    {
        $data = $this->loadAttribute->loadProductDataCollection($product_ids, $store_id);

        return (bool)$data->count();
    }

    /**
     * Return the attribute codes for all attributes currently used in
     * configurable products.
     *
     * @return array
     */
    public function getConfigurableAttributes()
    {
        return $this->loadAttribute->getConfigurableAttributes();
    }

    /**
     * Return a list of all Magento attributes that are used by Product Sync
     * when collecting product data.
     *
     * @return array
     */
    public function getUsedMagentoAttributes()
    {
        return $this->loadAttribute->getUsedMagentoAttributes();
    }

    /**
     * Return a map of Klevu attributes to Magento attributes.
     *
     * @return array
     * @deprecated protected method can, not use method in \Klevu\Search\Model\Product\LoadAttribute
     * Method is no longer called
     */
    protected function getAttributeMap()
    {
        return [];
    }

    /**
     * Returns an array of all automatically matched attributes. Include defaults and filterable
     * in search attributes.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAutomaticAttributes()
    {
        return $this->loadAttribute->getAutomaticAttributes();
    }

    /**
     * Takes system configuration attribute data and adds to $attribute_map
     *
     * @param $attribute_map
     * @param $additional_attributes
     *
     * @return array
     * @deprecated protected method can, not use method in \Klevu\Search\Model\Product\LoadAttribute
     * Method is no longer called
     */
    protected function prepareAttributeMap($attribute_map, $additional_attributes)
    {
        return [];
    }

    /**
     * Return the attribute codes for all filterable in search attributes.
     *
     * @return array
     * @deprecated protected method, can not use method in \Klevu\Search\Model\Product\LoadAttribute
     * Method is no longer called
     */
    protected function getLayeredNavigationAttributes()
    {
        return [];
    }
}
