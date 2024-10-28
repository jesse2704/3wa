<?php
namespace PersonalizeAI\SmartRecommend\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;

/**
 * Class Index
 *
 * This class handles the default action for the Index controller.
 */
class Index extends Action
{
    /**
     * Execute method to load and render the layout.
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
