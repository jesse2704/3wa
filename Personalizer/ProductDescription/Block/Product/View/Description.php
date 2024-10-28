<?php
namespace Personalizer\ProductDescription\Block\Product\View;

use Magento\Framework\DataObject;
use Magento\Framework\App\ObjectManager;

use Magento\Catalog\Model\Product;

class Description extends \Magento\Catalog\Block\Product\View\Description
{
    public function getPersonalizeCookie()
    {
        $objectManager = ObjectManager::getInstance();
        $cookieManager = $objectManager->get(\Magento\Framework\Stdlib\CookieManagerInterface::class);
        if ($cookieManager->getCookie('personalize_accepted') == true) {
            return true;
        }
        return false;
    }

    public function getApiPlace()
    {
        $objectManager = ObjectManager::getInstance();
        $scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        return $scopeConfig->getValue('productdescription/general/api', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getApiInstruction()
    {
        $objectManager = ObjectManager::getInstance();
        $scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        return $scopeConfig->getValue('productdescription/general/instruction', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
