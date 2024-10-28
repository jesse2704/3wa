<?php
namespace PersonalizeAI\SmartRecommend\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class PrivacyPolicy
 *
 * This class handles the display of the privacy policy page.
 */
class PrivacyPolicy extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * PrivacyPolicy constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Execute method to render the privacy policy page.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // Create and return the result page for the privacy policy
        return $this->resultPageFactory->create();
    }
}
