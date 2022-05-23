<?php

namespace Klevu\Troubleshoot\Model\Actions;

use Klevu\Search\Model\Klevu\KlevuFactory as Klevu_Factory;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Klevu\Troubleshoot\Model\TroubleshootContext as Klevu_TroubleshootContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Api\Data\StoreInterface;

/**
 * @deprecated method no longer required, data is fetched from Klevu Search moddule
 */
class Common extends AbstractModel
{
    /**
     * @var MagentoProductActionsInterface|mixed
     */
    private $magentoProductActions;

    public function __construct(
        Context $mcontext,
        Klevu_TroubleshootContext $context,
        Klevu_Factory $klevuFactory,
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
     * Returns parent ids for child id
     *
     * @param $ids
     *
     * @return array
     * @deprecated method no longer required, data is fetched from Klevu Search module
     */
    public function getParentRelationsByChild($ids)
    {
        return $this->magentoProductActions->getParentRelationsByChild($ids);
    }

    /**
     * Get product collection from klevu product sync
     *
     * @param StoreInterface $store
     *
     * @return mixed
     * @deprecated method no longer required, data is fetched from Klevu Search module
     */
    public function getKlevuProductCollection(StoreInterface $store)
    {
        return $this->magentoProductActions->getKlevuProductCollection($store);
    }
}
