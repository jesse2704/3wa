<?php
namespace PersonalizeAI\SmartRecommend\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Checkout\Model\Session as CheckoutSession;
use PersonalizeAI\SmartRecommend\Model\ExplicitDataService;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Catalog\Model\Product;

/**
 * Test case for the getCartItems method of ExplicitDataService
 *
 * Run test:
 * sudo ./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist
 * --no-extensions app/code/PersonalizeAI/SmartRecommend/Test/Unit/Model/CartItemsTest.php
 */
class CartItemsTest extends TestCase
{
    /**
     * @var ExplicitDataService
     */
    protected $explicitDataService;

    /**
     * @var CheckoutSession|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $checkoutSessionMock;

    /**
     * Set up the test environment before each test
     */
    protected function setUp(): void
    {
        // Create mocks for the main dependencies
        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);
        
        // Create additional mock objects required for ExplicitDataService constructor
        $customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $viewedProductsBlockMock = $this->createMock(\Magento\Reports\Block\Product\Viewed::class);
        $orderCollectionFactoryMock = $this->createMock(
            \Magento\Sales\Model\ResourceModel\Order\CollectionFactory::class
        );
        $productRepositoryMock = $this->createMock(\Magento\Catalog\Model\ProductRepository::class);
        $wishlistFactoryMock = $this->createMock(\Magento\Wishlist\Model\WishlistFactory::class);
        $extraPersonalizationHelperMock = $this->createMock(
            \PersonalizeAI\SmartRecommend\Helper\ExtraPersonalization::class
        );

        // Instantiate ExplicitDataService with mocked dependencies
        $this->explicitDataService = new ExplicitDataService(
            $customerSessionMock,
            $viewedProductsBlockMock,
            $orderCollectionFactoryMock,
            $productRepositoryMock,
            $this->checkoutSessionMock,
            $wishlistFactoryMock,
            $extraPersonalizationHelperMock
        );
    }

    /**
     * Test the getCartItems method
     */
    public function testGetCartItems()
    {
        // Create mocks for Quote, QuoteItem, and Product
        $quoteMock = $this->createMock(Quote::class);
        $quoteItemMock = $this->createMock(QuoteItem::class);
        $productMock = $this->createMock(Product::class);

        // Configure the product mock
        $productMock->method('getId')->willReturn(1);

        // Configure the quote item mock
        $quoteItemMock->method('getProduct')->willReturn($productMock);
        $quoteItemMock->method('getName')->willReturn('Test Product');
        $quoteItemMock->method('getSku')->willReturn('TEST-SKU');
        $quoteItemMock->method('getQty')->willReturn(2);
        $quoteItemMock->method('getPrice')->willReturn(9.99);

        // Configure the quote mock
        $quoteMock->method('getAllVisibleItems')->willReturn([$quoteItemMock]);

        // Configure the checkout session mock to return the quote mock
        $this->checkoutSessionMock->method('getQuote')->willReturn($quoteMock);

        // Define the expected result
        $expectedResult = [
            [
                'id' => 1,
                'name' => 'Test Product',
                'sku' => 'TEST-SKU',
                'qty' => 2,
                'price' => 9.99,
            ]
        ];

        // Call the method under test
        $result = $this->explicitDataService->getCartItems();

        // Assert that the result matches the expected output
        $this->assertEquals($expectedResult, $result);
    }
}
