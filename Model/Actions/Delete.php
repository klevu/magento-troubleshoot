<?php

namespace Klevu\Troubleshoot\Model\Actions;

use Klevu\Search\Model\Klevu\KlevuFactory as Klevu_Factory;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Klevu\Troubleshoot\Model\Actions\Common as Klevu_CommonAction;
use Klevu\Troubleshoot\Model\TroubleshootContext as Klevu_TroubleshootContext;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as Magento_CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Api\Data\StoreInterface;

class Delete extends AbstractModel
{
    /**
     * @var MagentoProductActionsInterface
     */
    private $magentoProductActions;

    public function __construct(
        Context $mcontext,
        Klevu_TroubleshootContext $context,
        Magento_CollectionFactory $magentoCollectionFactory,
        Klevu_Factory $klevuFactory,
        Klevu_CommonAction $klevuCommonAction,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        MagentoProductActionsInterface $magentoProductActions = null
    ) {
        parent::__construct($mcontext, $registry, $resource, $resourceCollection, $data);
        $this->magentoProductActions = $magentoProductActions ?: ObjectManager::getInstance()->get(MagentoProductActionsInterface::class);
    }

    /**
     * Returns ids which are expected for delete operaton
     *
     * @param StoreInterface $store
     * @return array
     */
    public function getQueueIds(StoreInterface $store)
    {
        return $this->magentoProductActions->deleteProductCollection($store);
    }
}
