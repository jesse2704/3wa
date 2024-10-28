<?php

namespace Personalizer\CookieManager\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CookieBanner extends Template
{
    protected $cookieManager;
    protected $cookieMetadataFactory;
    protected $sessionManager;
    protected $scopeConfig;

    public function __construct(
        Context $context,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Personalizer_CookieManager::cookie_banner.phtml');
    }

    public function getCookie()
    {
        return $this->cookieManager->getCookie('personalize_accepted');
    }

    public function getModuleConfig()
    {
        return $this->scopeConfig->getValue('cookiemanager/general/enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    protected function _prepareLayout()
    {
        $this->assign('block', $this);
        return parent::_prepareLayout();
    }
}
