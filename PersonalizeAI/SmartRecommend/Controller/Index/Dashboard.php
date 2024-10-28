<?php
namespace PersonalizeAI\SmartRecommend\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;

/**
 * Class Dashboard
 *
 * This class handles the dashboard actions.
 */
class Dashboard extends Action
{
    /**
     * Execute method to load and render the dashboard layout.
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
