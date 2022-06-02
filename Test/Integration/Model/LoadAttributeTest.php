<?php

namespace Klevu\TroubleShoot\Test\Integration\Model;

use Klevu\Search\Model\Product\LoadAttributeInterface;
use Klevu\Troubleshoot\Model\TroubleshootLoadAttribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class LoadAttributeTest extends TestCase
{

    public function test_IsProductLoadableViaCollection_CallsSearchModule()
    {
        $mockProductCollection = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchLoadAttributes = $this->getMockBuilder(LoadAttributeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchLoadAttributes->expects($this->once())
            ->method('loadProductDataCollection')
            ->willReturn($mockProductCollection);

        $troubleShootLoadAttribute = ObjectManager::getInstance()->create(TroubleshootLoadAttribute::class, [
            'loadAttribute' => $searchLoadAttributes
        ]);
        $troubleShootLoadAttribute->isProductLoadableViaCollection([]);
    }

    public function test_GetConfigurableAttributes_CallsSearchModule()
    {
        $searchLoadAttributes = $this->getMockBuilder(LoadAttributeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchLoadAttributes->expects($this->once())->method('getConfigurableAttributes');

        $troubleShootLoadAttribute = ObjectManager::getInstance()->create(TroubleshootLoadAttribute::class, [
            'loadAttribute' => $searchLoadAttributes
        ]);
        $troubleShootLoadAttribute->getConfigurableAttributes([]);
    }

    public function test_GetUsedMagentoAttributes_CallsSearchModule()
    {
        $searchLoadAttributes = $this->getMockBuilder(LoadAttributeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchLoadAttributes->expects($this->once())->method('getUsedMagentoAttributes');

        $troubleShootLoadAttribute = ObjectManager::getInstance()->create(TroubleshootLoadAttribute::class, [
            'loadAttribute' => $searchLoadAttributes
        ]);
        $troubleShootLoadAttribute->getUsedMagentoAttributes([]);
    }

    public function test_GetAutomaticAttributes_CallsSearchModule()
    {
        $searchLoadAttributes = $this->getMockBuilder(LoadAttributeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchLoadAttributes->expects($this->once())->method('getAutomaticAttributes');

        $troubleShootLoadAttribute = ObjectManager::getInstance()->create(TroubleshootLoadAttribute::class, [
            'loadAttribute' => $searchLoadAttributes
        ]);
        $troubleShootLoadAttribute->getAutomaticAttributes([]);
    }
}
