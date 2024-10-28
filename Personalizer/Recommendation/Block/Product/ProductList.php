<?php
namespace Personalizer\Recommendation\Block\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Zend_Db_Select;
use Magento\Framework\App\ObjectManager;
use Magento\Reports\Block\Product\Viewed as ReportProductViewed;

class ProductList extends \Magento\CatalogWidget\Block\Product\ProductsList
{
    protected $cookieManager;
    public $viewed;
    
    /**
     * Retrieve loaded category collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection|null
     */
    public function getProductCollection()
    {
        $collection = $this->productCollectionFactory->create()->addAttributeToSelect(array('*'));

        $objectManager = ObjectManager::getInstance();
        $this->cookieManager = $objectManager->get(\Magento\Framework\Stdlib\CookieManagerInterface::class);

        if ($this->cookieManager->getCookie('personalize_accepted') && $this->getModuleEnable() == 1) {

            $collection->addAttributeToSelect('ordering');

            // $collection = $this->addUniqueOrder($collection);

            $collection->setOrder('ordering', 'ASC');

            // foreach ($collection->getItems() as $product) {
            //     echo $product['sku'].'= '.$product['ordering'] . ", ";
            // }
        }   

       $this->getViewedCollection();

        return $collection;
    }

    public function getViewedCollection()
    {
        $objectManager = ObjectManager::getInstance();
        $this->viewed = $objectManager->get(\Magento\Reports\Block\Product\Viewed::class);
        
        // Use getItemsCollection() instead of create()
        $collection = $this->viewed->getItemsCollection()->addAttributeToSelect(['*']);

        foreach ($collection->getItems() as $product) {
           // echo $product['sku'] . ", ";
        }
        
        $this->cookieManager = $objectManager->get(\Magento\Framework\Stdlib\CookieManagerInterface::class);
    
        if ($this->cookieManager->getCookie('personalize_accepted') && $this->getModuleEnable() == 1) {
            // Your personalization logic here
        }

    }


    public function addUniqueOrder(\Magento\Catalog\Model\ResourceModel\Product\Collection $collection) {
        $productList = "";
        foreach ($collection->getItems() as $product) {
            $productList .= $product['entity_id'] . "\n" . $product['sku'] . "\n";
        }

        $order = $this->listAi($productList);

        $collection->getSelect()->joinLeft(
            ['product_entity' => $collection->getTable('catalog_product_entity')],
            'e.entity_id = product_entity.entity_id',
            ['ordering' => new \Zend_Db_Expr(0)]
        );

        foreach ($collection->getItems() as $product) {
            $product->setData('ordering', rand(1, 10));
        }

        return $collection;
    }

    public function listAi(String $list) {
        $list = str_replace("\r", "", $list);
        $list = str_replace("\n", "", $list);
        $url = 'https://ai-server.regem.in/api/index.php';
        $data = array('input' => "Order deze lijst van meest relevant naar minder relevant (voor iemand met de volgende interesses: ". $this->cookieManager->getCookie('personalize_accepted') .") : $list (let op het volgende: stuur alleen de lijst terug, " . $this->getApiInstruction() . ")");

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
        // die(http_build_query($data) . $aioutput);
        return $aioutput;
    }

    public function getModuleEnable()
    {
        $objectManager = ObjectManager::getInstance();
        $scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        return $scopeConfig->getValue('recommendation/general/enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
