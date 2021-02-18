<?php

namespace Klevu\Troubleshoot\Controller\Adminhtml\Troubleshoot;


use Klevu\Troubleshoot\Block\Adminhtml\TroubleshootResult;
use Klevu\Troubleshoot\Model\Troubleshoot as TroubleshootModel;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
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
     * @var Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;
    /**
     * @var PageFactory
     */
    private $resultPageFactory;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var TroubleshootModel
     */
    private $troubleshootModel;

    /**
     * Post constructor.
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
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->troubleshootModel = $troubleshootModel;
        $this->formKeyValidator = $context->getFormKeyValidator();
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        if (!$this->getRequest()->isXmlHttpRequest()
            || !$this->formKeyValidator->validate($this->getRequest())) {
            $this->_forward('noroute');
            return;
        }

        $params = array(
            'store_id' => (int)$this->getRequest()->getParam('store_id'),
            'product_id' => (int)$this->getRequest()->getParam('product_id')
        );

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultPage = $this->resultPageFactory->create();

        $blockData = $resultPage->getLayout()
            ->createBlock(TroubleshootResult::class)
            ->setTemplate('Klevu_Troubleshoot::troubleshoot-result.phtml')
            ->addData($params)
            ->toHtml();
        $resultJson->setData(['blockdata' => $blockData]);
        return $resultJson;
    }

}
