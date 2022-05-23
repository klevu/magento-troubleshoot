<?php

namespace Klevu\Troubleshoot\Model;

use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;
use Klevu\Search\Model\Product\ProductIndividualInterface as Klevu_Product_Individual;
use Klevu\Search\Model\Product\ProductParentInterface as Klevu_Product_Parent;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider as Magento_OptionProvider;
use Magento\Framework\App\ProductMetadataInterface as Klevu_Product_Meta;
use Magento\Framework\App\ResourceConnection as Klevu_ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface as Klevu_StoreManagerInterface;

/**
 * Class TroubleshootContext
 * @package Klevu\Troubleshoot\Model
 */
class TroubleshootContext extends DataObject
{
    /**
     * TroubleshootContext constructor.
     * @param Klevu_HelperManager $helperManager
     * @param Klevu_ResourceConnection $resourceConnection
     * @param Klevu_StoreManagerInterface $storeManagerInterface
     * @param Klevu_Product_Meta $klevuProductMeta
     * @param Klevu_Product_Individual $klevuProductIndividual
     * @param Klevu_Product_Parent $klevuProductParent
     * @param Magento_OptionProvider $magentoOptionProvider
     */
    public function __construct(
        Klevu_HelperManager $helperManager,
        Klevu_ResourceConnection $resourceConnection,
        Klevu_StoreManagerInterface $storeManagerInterface,
        Klevu_Product_Meta $klevuProductMeta,
        Klevu_Product_Individual $klevuProductIndividual,
        Klevu_Product_Parent $klevuProductParent,
        Magento_OptionProvider $magentoOptionProvider
    )
    {
        $data = [
            'helper_manager' => $helperManager,
            'resource_connection' => $resourceConnection,
            'store_manager_interface' => $storeManagerInterface,
            'klevu_product_meta' => $klevuProductMeta,
            'klevu_product_individual' => $klevuProductIndividual,
            'klevu_product_parent' => $klevuProductParent,
            'magento_option_provider' => $magentoOptionProvider
        ];
        parent::__construct($data);
    }
}
