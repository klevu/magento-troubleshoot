<?php

namespace Klevu\TroubleShoot\Test\Integration\Model\Actions;

use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Klevu\Troubleshoot\Model\Actions\Add;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation disabled
 */
class AddActionTest extends TestCase
{
    /**
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testAddActionCallsSearchModule()
    {
        $mockSearchActions = $this->getMockBuilder(MagentoProductActionsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSearchActions->expects($this->once())->method('addProductCollection');

        $addAction = ObjectManager::getInstance()->create(Add::class, [
            'magentoProductActions' => $mockSearchActions
        ]);
        $store = $this->getStore();
        $addAction->getQueueIds($store);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode = 'klevu_test_store_1')
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = ObjectManager::getInstance()->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../_files/websiteFixtures_rollback.php';
    }
}
