<?php
namespace PersonalizeAI\SmartRecommend\Block;

use Magento\Framework\View\Element\Template;
use PersonalizeAI\SmartRecommend\Controller\Index\Dashboard as IndexController;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Reports\Block\Product\Viewed as ViewedProductsBlock;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Wishlist\Model\WishlistFactory;
use PersonalizeAI\SmartRecommend\Helper\ExtraPersonalization;
use Personalizer\Recommendation\Block\Product\RecommondationService;
use PersonalizeAI\SmartRecommend\Model\ExplicitDataService;
use Magento\Framework\Escaper;

class Dashboard extends Template
{
    /**
     * @var IndexController
     */
    protected $indexController;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var ViewedProductsBlock
     */
    private $viewedProductsBlock;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var WishlistFactory
     */
    protected $wishlistFactory;

    /**
     * @var ExtraPersonalizationHelper
     */
    protected $extraPersonalizationHelper;

    /**
     * @var RecommondationService
     */
    public $personalizeRecommondationAI;

    /**
     * @var ExplicitDataService
     */
    public $explicitDataService;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * Dashboard constructor.
     *
     * @param Template\Context $context
     * @param IndexController $indexController
     * @param CollectionFactory $orderCollectionFactory
     * @param CustomerSession $customerSession
     * @param ViewedProductsBlock $viewedProductsBlock
     * @param CheckoutSession $checkoutSession
     * @param WishlistFactory $wishlistFactory
     * @param ExtraPersonalization $extraPersonalizationHelper
     * @param RecommondationService $personalizeRecommondationAI
     * @param ExplicitDataService $explicitDataService
     * @param Escaper $escaper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        IndexController $indexController,
        CollectionFactory $orderCollectionFactory,
        CustomerSession $customerSession,
        ViewedProductsBlock $viewedProductsBlock,
        CheckoutSession $checkoutSession,
        WishlistFactory $wishlistFactory,
        ExtraPersonalization $extraPersonalizationHelper,
        RecommondationService $personalizeRecommondationAI,
        ExplicitDataService $explicitDataService,
        Escaper $escaper,
        array $data = []
    ) {
        // Initialize properties
        $this->indexController = $indexController;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->customerSession = $customerSession;
        $this->viewedProductsBlock = $viewedProductsBlock;
        $this->checkoutSession = $checkoutSession;
        $this->wishlistFactory = $wishlistFactory;
        $this->extraPersonalizationHelper = $extraPersonalizationHelper;
        $this->personalizeRecommondationAI = $personalizeRecommondationAI;
        $this->explicitDataService = $explicitDataService;
        $this->escaper = $escaper;

        parent::__construct($context, $data);
    }

    /**
     * Get data from the controller.
     *
     * @return mixed
     */
    public function getControllerData()
    {
        return $this->indexController->getDataLayer();
    }

    /**
     * Get personalized prompts based on admin settings from ExtraPersonalizer Helper and interests.
     *
     * @return string
     */
    public function getPrompt(): string
    {
        // Fetch prompts based on user behavior and preferences
        $prompts = $this->extraPersonalizationHelper->getExtraPersonalizationPrompts();
        $personalizedInfo = [];

        if (isset($prompts['viewed_items'])) {
            // Retrieve viewed products and format them for display
            $viewedProducts = $this->getViewedProducts();
            if (!empty($viewedProducts)) {
                foreach ($viewedProducts as $product) {
                    // Collect viewed item data for personalization
                    if ($product) {
                        // Use the injected Escaper to escape HTML output properly.
                        $personalizedInfo[] = [
                            'name' => $this->escaper->escapeHtml($product->getName()),
                            'sku' =>  $this->escaper->escapeHtml($product->getSku())
                        ];
                    }
                }
            }
            // Format the list of viewed items for display
            if (!empty($personalizedInfo)) {
                return "Recently viewed items: " . implode(', ', array_column($personalizedInfo, 'name'));
            }
        }

        return "No personalized prompts available.";
    }

    /**
     * Format item list for display.
     *
     * @param string $title The title for the item list.
     * @param array  $items The items to format.
     *
     * @return string Formatted item list.
     */
    private function formatItemList(string $title, array $items): string
    {
        if (empty($items)) {
            return "$title: None";
        }

        // Prepare a list of item names for display.
        return "$title: " . implode(', ', array_column($items, 'name'));
    }

    /**
     * Format price for display.
     *
     * @param float|int|string|null $price The price to format.
     *
     * @return string Formatted price.
     */
    public function formatPrice($price): string
    {
        return $this->escaper->escapeHtml($this->_storeManager->getStore()->getBaseCurrency()->format($price));
    }

    /**
     * Get explicit data service instance.
     *
     * @return ExplicitDataService
     */
    public function getExplicitDataService(): ExplicitDataService
    {
        return $this->explicitDataService;
    }

    /**
     * Get personalized recommendation AI instance.
     *
     * @return RecommondationService
     */
    public function getPersonalizeRecommondationAI(): RecommondationService
    {
        return $this->personalizeRecommondationAI;
    }
}
