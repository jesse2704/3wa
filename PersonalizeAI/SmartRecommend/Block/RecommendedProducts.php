<?php
namespace PersonalizeAI\SmartRecommend\Block;

use Magento\Catalog\Block\Product\ListProduct;
use Personalizer\Recommendation\Block\Product\RecommondationService;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * Class RecommendedProducts
 *
 * This class is responsible for fetching and displaying recommended products.
 */
class RecommendedProducts extends ListProduct
{
    /**
     * @var RecommondationService
     */
    protected $personalizeRecommondationAI;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * RecommendedProducts constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param RecommondationService $personalizeRecommondationAI
     * @param CollectionFactory $productCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        RecommondationService $personalizeRecommondationAI,
        CollectionFactory $productCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        CookieManagerInterface $cookieManager,
        array $data = []
    ) {
        $this->personalizeRecommondationAI = $personalizeRecommondationAI;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->cookieManager = $cookieManager;
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );
    }

    /**
     * Get the loaded product collection based on recommendations.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getLoadedProductCollection()
    {
        if ($this->isModuleEnabled()) {
            $recommendations = $this->personalizeRecommondationAI->generateRecommendation();
            
            if (empty($recommendations)) {
                return $this->productCollectionFactory->create();
            }

            $productIds = array_column($recommendations, 'id');

            $collection = $this->productCollectionFactory->create();
            $collection->addIdFilter($productIds)
                ->addAttributeToSelect('*');

            $orderByField = new \Zend_Db_Expr("FIELD(e.entity_id, " . implode(',', $productIds) . ")");
            $collection->getSelect()->order($orderByField);

            return $collection;
        } else {
            // Default product collection for widget
            return parent::getLoadedProductCollection();
        }
    }

    /**
     * Format the price for display.
     *
     * @param float $price The price to format.
     * @param bool $includeContainer Whether to include currency container.
     * @return string Formatted price.
     */
    public function formatPrice($price, $includeContainer = true)
    {
        if ($includeContainer) {
            return $this->_storeManager->getStore()->getBaseCurrency()->format($price);
        } else {
            return number_format($price, 2, '.', '');
        }
    }

    private function isModuleEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            'smartrecommend_settings/general/enable',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getTemplate()
    {
        $personalizationEnabled = $this->cookieManager->getCookie('personalizationToggler');
        
        if ($personalizationEnabled === 'true' && $this->isModuleEnabled()) {
            return 'PersonalizeAI_SmartRecommend::recommended_products.phtml';
        } else {
            return 'Magento_CatalogWidget::product/widget/content/grid.phtml';
        }
    }
}

   