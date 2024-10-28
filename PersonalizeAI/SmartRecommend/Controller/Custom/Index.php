<?php
namespace PersonalizeAI\SmartRecommend\Controller\Index;

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;

class Index extends Action
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
