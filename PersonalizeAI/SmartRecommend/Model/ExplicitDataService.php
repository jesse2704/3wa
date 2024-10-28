<?php
namespace PersonalizeAI\SmartRecommend\Model;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Reports\Block\Product\Viewed as ViewedProductsBlock;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Wishlist\Model\WishlistFactory;
use PersonalizeAI\SmartRecommend\Helper\ExtraPersonalization;

class ExplicitDataService
{
    protected $customerSession;
    protected $viewedProductsBlock;
    protected $orderCollectionFactory;
    protected $productRepository;
    protected $searchCriteriaBuilder;
    protected $visibility;
    protected $storeManager;
    protected $checkoutSession;
    protected $wishlistFactory;
    protected $extraPersonalizationHelper;

    public function __construct(
        CustomerSession $customerSession,
        ViewedProductsBlock $viewedProductsBlock,
        CollectionFactory $orderCollectionFactory,
        ProductRepository $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Visibility $visibility,
        StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        WishlistFactory $wishlistFactory,
        ExtraPersonalization $extraPersonalizationHelper
    ) {
        $this->customerSession = $customerSession;
        $this->viewedProductsBlock = $viewedProductsBlock;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->visibility = $visibility;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->wishlistFactory = $wishlistFactory;
        $this->extraPersonalizationHelper = $extraPersonalizationHelper;
    }

    /**
     * Get viewed products for the current customer.
     * // I have enabled reports in magento ADMIN Stores/Configuration/General/Reports SET 'Yes'
     * @return array
     */
    public function getViewedProducts()
    {
        $customerId = $this->customerSession->getCustomerId();
        
        if (!$customerId) {
            return $this->viewedProductsBlock->getItemsCollection();
        }
        
        // Collection of viewed products, not filtered by customer_id
        $collection = $this->viewedProductsBlock->getItemsCollection();
        
        // Check if the join has already been added
        $select = $collection->getSelect();
        $fromPart = $select->getPart(\Zend_Db_Select::FROM);
        
        if (!isset($fromPart['rvi'])) {
            // Join with report_viewed_product_index table to filter by customer
            $collection->getSelect()->join(
                ['rvi' => $collection->getTable('report_viewed_product_index')],
                'e.entity_id = rvi.product_id AND rvi.customer_id = ' . (int)$customerId,
                []
            );
        }
        
        return $collection;
    }

    /**
     * Get bought products for the current customer.
     *
     * @return array
     */
    public function getBoughtProducts()
    {
        // Check if customer logged in
        if (!$this->customerSession->isLoggedIn()) {
            return [];
        }
    
        $customerId = $this->customerSession->getCustomerId();
        
        // If customer does not exist return
        if (!$customerId) {
            return [];
        }
        
        // Retrieve all orders and filter
        $orders = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', ['in' => ['complete', 'processing', 'pending']]);
    
        $boughtProducts = [];
        foreach ($orders as $order) {
            foreach ($order->getAllVisibleItems() as $item) {
                try {
                    // Retrieve items in the order
                    $product = $this->productRepository->getById($item->getProductId());
                    $boughtProducts[] = [
                        'id' => $product->getId(),
                        'sku' => $product->getSku(),
                        'name' => $product->getName(),
                        'price' => $item->getPrice(),
                        'qty' => $item->getQtyOrdered(),
                        'order_id' => $order->getIncrementId(),
                        'order_date' => $order->getCreatedAt(),
                        'order_status' => $order->getStatus()
                    ];
                } catch (\Exception $e) {
                    return 'Error';
                }
            }
        }
    
        return $boughtProducts;
    }

    public function getCartItems()
    {
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();
        $cartItems = [];

        foreach ($items as $item) {
            $cartItems[] = [
                'id' => $item->getProduct()->getId(),
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'qty' => $item->getQty(),
                'price' => $item->getPrice()
            ];
        }

        return $cartItems;
    }

    public function getWishlistItems()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return [];
        }
    
        $customerId = $this->customerSession->getCustomerId();
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId, true);
        $wishlistItems = [];
    
        foreach ($wishlist->getItemCollection() as $item) {
            $product = $item->getProduct();
            $wishlistItems[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'sku' => $product->getSku(),
                'price' => $product->getFinalPrice(),
            ];
        }
    
        return $wishlistItems;
    }
    // Extra:: Category browsing history:

    public function getPrompt()
    {
        $prompts = $this->extraPersonalizationHelper->getExtraPersonalizationPrompts();
        $personalizedInfo = [];

        if (isset($prompts['viewed_items'])) {
            $viewedProducts = $this->getViewedProducts();
            $viewedItems = $this->formatItems($viewedProducts);
            $personalizedInfo[] = $this->formatItemList("Recently viewed items", $viewedItems);
        }

        if (isset($prompts['bought_items'])) {
            $boughtItems = $this->formatItems($this->getBoughtProducts());
            $personalizedInfo[] = $this->formatItemList("Previously bought items", $boughtItems);
        }

        if (isset($prompts['cart_items'])) {
            $cartItems = $this->formatItems($this->getCartItems());
            $personalizedInfo[] = $this->formatItemList("Items currently in cart", $cartItems);
        }

        if (isset($prompts['wish_list'])) {
            $wishlistItems = $this->formatItems($this->getWishlistItems());
            $personalizedInfo[] = $this->formatItemList("Items in wishlist", $wishlistItems);
        }

        if (isset($prompts['category_browsing'])) {
            // category browsing logic extra
            // $personalizedInfo[] = $this->formatCategoryList("Recently browsed categories", $categories);
        }

        $finalPrompt = "Personalize the response based on the following user information:\n\n";
        $finalPrompt .= implode("\n\n", $personalizedInfo);
        $finalPrompt .= "\n\nUse this information to tailor your recommendations and responses to the user's preferences and behavior.";

        return $finalPrompt;
    }

    // Format items for viewed products
    private function formatItems($items)
    {
        $formattedItems = [];
        foreach ($items as $item) {
            $formattedItems[] = [
                'name' => $this->getItemProperty($item, 'name'),
                'sku' => $this->getItemProperty($item, 'sku')
            ];
        }
        return $formattedItems;
    }

    private function getItemProperty($item, $property)
    {
        // Check if item is object
        if (is_object($item)) {
            $getter = 'get' . ucfirst($property);
            return $item->$getter() ?? null;
        }
        
        //If item is not object treat as array
        return $item[$property] ?? null;
    }

    private function formatItemList($title, $items)
    {
        // Extract all 'name' values from the items array
        $itemNames = array_column($items, 'name');

        // Concatenate title + item names
        return $title . ": " . implode(", ", $itemNames);
    }

        // Extra item, i will add if enough time
    // private function formatCategoryList($title, $categories)
    // {
     
    // }

    // Retrieve all items in store
    public function getAllProducts()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->addFilter('visibility', $this->visibility->getVisibleInSiteIds(), 'in')
            ->addFilter('store_id', $this->storeManager->getStore()->getId())
            ->create();

        $productList = $this->productRepository->getList($searchCriteria);
        $products = [];

        foreach ($productList->getItems() as $product) {
            $products[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'color' => $product->getAttributeText('color')
            ];
        }

        return $products;
    }
}
