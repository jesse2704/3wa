<?php
namespace Personalizer\ProductDescription\Observer;

use CURLFile;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ObserverInterface;
use Personalizer\ProductDescription\Controller\Api\DescriptionApi;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ModifyProductDescription implements ObserverInterface
{
    protected $cookieManager;
    protected $scopeConfig;

    public function __construct(
        \Personalizer\CookieManager\Block\CookieBanner $cookieManager,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->cookieManager = $cookieManager;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->cookieManager->getCookie() && $this->scopeConfig->getValue('productdescription/general/enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 1) {
            $product = $observer->getEvent()->getProduct();

            $modifiedDescription = $product->getDescription();

            $startPos = strpos($modifiedDescription, '<p>');
            $endPos = strrpos($modifiedDescription, '</p>');
            $length = $endPos - $startPos + 4;
            $modifiedDescription = substr($modifiedDescription, $startPos, $length);

            if ($this->getApiPlace() == 'backend') {
                 $modifiedDescription = $this->ApiAITool($modifiedDescription);
            }

            $product->setDescription($modifiedDescription);
        }
    }

    protected function ApiAITool($userinput): string
    {
        $userinput = str_replace("\r", "", $userinput);
        $userinput = str_replace("\n", "", $userinput);

        $url = 'https://ai-server.regem.in/api/index.php';
        $data = array('input' => "Herschrijf de volgende tekst (voor een persoon met de volgende interesses: ". $this->cookieManager->getCookie() .") : $userinput (let op het volgende:" . $this->getApiInstruction() . ")");

        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === FALSE) {
            // Handle error
            return "Error occurred";
        }

        $aioutput = $result;

        if (strpos($aioutput, "Try Again! or May be Server is Down!") !== false) {
            $aioutput = "Try Again, Sorry about it.";
        } elseif (strpos($aioutput, "regem") !== false) {
            $aioutput = str_replace("regem", "openai", $aioutput);
        } elseif (strpos($aioutput, "Regem") !== false) {
            $aioutput = str_replace("Regem", "Openai", $aioutput);
        }

        return $aioutput;
    }

    public function getApiPlace()
    {
        return $this->scopeConfig->getValue('productdescription/general/api', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getApiInstruction()
    {
        return $this->scopeConfig->getValue('productdescription/general/instruction', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
