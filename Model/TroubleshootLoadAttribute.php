<?php


namespace Klevu\Troubleshoot\Model;


use Klevu\Troubleshoot\Model\TroubleshootContext as Klevu_TroubleshootContext;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as Magento_CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel as AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Class TroubleshootLoadAttribute
 * @package Klevu\Troubleshoot\Model
 */
class TroubleshootLoadAttribute extends AbstractModel
{

    /**
     * @var
     */
    private $_storeModelStoreManagerInterface;

    /**
     * @var
     */
    private $_frameworkModelResource;

    /**
     * @var Magento_CollectionFactory
     */
    private $_magentoCollectionFactory;

    /**
     * @var
     */
    private $_searchHelperConfig;

    /**
     * TroubleshootLoadAttribute constructor.
     * @param Context $mcontext
     * @param TroubleshootContext $context
     * @param Magento_CollectionFactory $magentoCollectionFactory
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $mcontext,
        Klevu_TroubleshootContext $context,
        Magento_CollectionFactory $magentoCollectionFactory,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [])
    {
        parent::__construct($mcontext, $registry, $resource, $resourceCollection, $data);
        $this->_storeModelStoreManagerInterface = $context->getStoreManagerInterface();
        $this->_magentoCollectionFactory = $magentoCollectionFactory;
        $this->_frameworkModelResource = $context->getResourceConnection();
        $this->_searchHelperConfig = $context->getHelperManager()->getConfigHelper();

    }

    /**
     * Returns requested the store object
     *
     * @param null $store_id
     * @return mixed
     */
    private function getStoreObject($store_id = null)
    {
        if (!empty($store_id)) {
            $store = $this->_storeModelStoreManagerInterface->getStore($store_id);
        } else {
            $store = $this->_storeModelStoreManagerInterface->getStore();
        }
        return $store;
    }

    /**
     * Checks whether data loading using magento collection method
     *
     * @param $product_ids
     * @param null $store_id
     * @return bool
     *
     * Source: \Klevu\Search\Model\Product\LoadAttribute::loadProductDataCollection
     */
    public function isProductLoadableViaCollection($product_ids, $store_id = null)
    {
        $store = $this->getStoreObject($store_id);
        $attributes = $this->getUsedMagentoAttributes();

        $collection = $this->_magentoCollectionFactory->create()
            ->addAttributeToSelect($attributes)
            ->addIdFilter($product_ids)
            ->setStore($store)
            ->addStoreFilter()
            ->addMinimalPrice()
            ->addFinalPrice();
        $collection->setFlag('has_stock_status_filter', false);

        $collection->load()
            ->addCategoryIds();
        return $collection->count() > 0;
    }

    /**
     * Return the attribute codes for all attributes currently used in
     * configurable products.
     *
     * @return array
     *
     * Source: \Klevu\Search\Model\Product\LoadAttribute::getConfigurableAttributes
     */
    public function getConfigurableAttributes()
    {
        $select = $this->_frameworkModelResource->getConnection("core_write")
            ->select()
            ->from(
                ["a" => $this->_frameworkModelResource->getTableName("eav_attribute")],
                ["attribute" => "a.attribute_code"]
            )
            ->join(
                ["s" => $this->_frameworkModelResource->getTableName("catalog_product_super_attribute")],
                "a.attribute_id = s.attribute_id",
                ""
            )
            ->group(["a.attribute_code"]);
        return $this->_frameworkModelResource->getConnection("core_write")->fetchCol($select);
    }


    /**
     * Return a list of all Magento attributes that are used by Product Sync
     * when collecting product data.
     *
     * @return array
     *
     * Source: \Klevu\Search\Model\Product\LoadAttribute::getUsedMagentoAttributes
     */
    public function getUsedMagentoAttributes()
    {
        $result[] = array();
        foreach ($this->getAttributeMap() as $attributes) {
            $result[] = $attributes;
        }
        $result = call_user_func_array('array_merge', $result);
        $result = array_merge($result, $this->getConfigurableAttributes());
        return array_unique($result);
    }

    /**
     * Return a map of Klevu attributes to Magento attributes.
     *
     * @return array
     *
     * Source: \Klevu\Search\Model\Product\LoadAttribute::getAttributeMap
     */
    protected function getAttributeMap()
    {
        if (!$this->hasData('attribute_map')) {
            $attribute_map = [];
            $automatic_attributes = $this->getAutomaticAttributes();
            $attribute_map = $this->prepareAttributeMap($attribute_map, $automatic_attributes);

            // Add otherAttributeToIndex to $attribute_map.
            $otherAttributeToIndex = $this->_searchHelperConfig->getOtherAttributesToIndex($this->_storeModelStoreManagerInterface->getStore());

            if (!empty($otherAttributeToIndex)) {
                $attribute_map['otherAttributeToIndex'] = $otherAttributeToIndex;
            }
            // Add boostingAttribute to $attribute_map.
            $boosting_value = $this->_searchHelperConfig->getBoostingAttribute($this->_storeModelStoreManagerInterface->getStore());
            if ($boosting_value != "use_boosting_rule") {
                if (($boosting_attribute = $this->_searchHelperConfig->getBoostingAttribute($this->_storeModelStoreManagerInterface->getStore())) && !is_null($boosting_attribute)) {
                    $attribute_map['boostingAttribute'][] = $boosting_attribute;
                }
            }
            $this->setData('attribute_map', $attribute_map);
        }
        return $this->getData('attribute_map');
    }


    /**
     * Returns an array of all automatically matched attributes. Include defaults and filterable
     * in search attributes.
     *
     * @return array
     *
     * Source: \Klevu\Search\Model\Product\LoadAttribute::getAutomaticAttributes
     */
    public function getAutomaticAttributes()
    {
        if (!$this->hasData('automatic_attributes')) {
            // Default mapped attributes
            $default_attributes = $this->_searchHelperConfig->getDefaultMappedAttributes();
            $attributes = [];
            $iMaxDefaultAttrCnt = count($default_attributes['klevu_attribute']);
            for ($i = 0; $i < $iMaxDefaultAttrCnt; $i++) {
                $attributes[] = [
                    'klevu_attribute' => $default_attributes['klevu_attribute'][$i],
                    'magento_attribute' => $default_attributes['magento_attribute'][$i]
                ];
            }
            // Get all layered navigation / filterable in search attributes
            foreach ($this->getLayeredNavigationAttributes() as $layeredAttribute) {
                $attributes[] = [
                    'klevu_attribute' => 'other',
                    'magento_attribute' => $layeredAttribute
                ];
            }
            $this->setData('automatic_attributes', $attributes);
            // Update the store system config with the updated automatic attributes map.
            $this->_searchHelperConfig->setAutomaticAttributesMap($attributes, $this->_storeModelStoreManagerInterface->getStore());
        }
        return $this->getData('automatic_attributes');
    }

    /**
     * Takes system configuration attribute data and adds to $attribute_map
     *
     * @param $attribute_map
     * @param $additional_attributes
     *
     * @return array
     *
     * Source: \Klevu\Search\Model\Product\LoadAttribute::prepareAttributeMap
     */
    protected function prepareAttributeMap($attribute_map, $additional_attributes)
    {
        foreach ($additional_attributes as $mapping) {
            if (!isset($attribute_map[$mapping['klevu_attribute']])) {
                $attribute_map[$mapping['klevu_attribute']] = [];
            }
            $attribute_map[$mapping['klevu_attribute']][] = $mapping['magento_attribute'];
        }
        return $attribute_map;
    }


    /**
     * Return the attribute codes for all filterable in search attributes.
     *
     * @return array
     *
     * Source: \Klevu\Search\Model\Product\LoadAttribute::getLayeredNavigationAttributes
     */
    protected function getLayeredNavigationAttributes()
    {
        $attributes = $this->_searchHelperConfig->getDefaultMappedAttributes();
        $select = $this->_frameworkModelResource->getConnection("core_write")
            ->select()
            ->from(
                ["a" => $this->_frameworkModelResource->getTableName("eav_attribute")],
                ["attribute" => "a.attribute_code"]
            )
            ->join(
                ["ca" => $this->_frameworkModelResource->getTableName("catalog_eav_attribute")],
                "ca.attribute_id = a.attribute_id",
                ""
            )
            // Only if the attribute is filterable in search, i.e. attribute appears in search layered navigation.
            ->where("ca.is_filterable_in_search = ?", "1")
            // Make sure we exclude the attributes thar synced by default.
            ->where("a.attribute_code NOT IN(?)", array_unique($attributes['magento_attribute']))
            ->group(["attribute_code"]);
        return $this->_frameworkModelResource->getConnection("core_write")->fetchCol($select);
    }

}
