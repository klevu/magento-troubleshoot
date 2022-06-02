<?php

namespace Klevu\Troubleshoot\Controller\Adminhtml\Troubleshoot;

use Klevu\Troubleshoot\Block\Adminhtml\TroubleshootResult;
use Klevu\Troubleshoot\Model\Troubleshoot as TroubleshootModel;
use Magento\Backend\App\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Post
 * @package Klevu\Troubleshoot\Controller\Adminhtml\Troubleshoot
 */
class Post extends Action
{
    /**
     * Using the resource tag from Klevu_Search
     */
    const ADMIN_RESOURCE = 'Klevu_Search::config_search';
    /**
     * @var Validator
     */
    protected $formKeyValidator;
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * Post constructor.
     *
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param TroubleshootModel $troubleshootModel
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        TroubleshootModel $troubleshootModel
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->formKeyValidator = $this->_formKeyValidator;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest() || !$this->_formKeyValidator->validate($request)) {
            $this->_forward('noroute');

            return;
        }
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData([
            'blockdata' => $this->getBlockData($request)
        ]);

        return $resultJson;
    }

    /**
     * @param RequestInterface $request
     *
     * @return string
     */
    private function getBlockData(RequestInterface $request)
    {
        $resultPage = $this->resultPageFactory->create();

        $layout = $resultPage->getLayout();
        $blockData = $layout->createBlock(TroubleshootResult::class);
        $blockData->setTemplate('Klevu_Troubleshoot::troubleshoot-result.phtml');
        $blockData->addData([
            'store_id' => (int)$request->getParam('store_id'),
            'product_id' => (int)$request->getParam('product_id')
        ]);

        return $blockData->toHtml();
    }
}
