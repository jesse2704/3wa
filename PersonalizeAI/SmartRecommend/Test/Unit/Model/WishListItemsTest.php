<?php
namespace PersonalizeAI\SmartRecommend\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Wishlist\Model\WishlistFactory;
use PersonalizeAI\SmartRecommend\Model\ExplicitDataService;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\Item as WishlistItem;
use Magento\Catalog\Model\Product;

/**
 * Test case for the getWishlistItems method of ExplicitDataService
 *
 * Run test:
 * sudo ./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist
 * --no-extensions app/code/PersonalizeAI/SmartRecommend/Test/Unit/Model/WishListItemsTest.php
 */
class WishlistItemsTest extends TestCase
{
    /**
     * @var ExplicitDataService
     */
    protected $explicitDataService;

    /**
     * @var CustomerSession|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $customerSessionMock;

    /**
     * @var WishlistFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $wishlistFactoryMock;

    /**
     * Set up the test environment before each test
     */
    protected function setUp(): void
    {
        // Create mocks for the main dependencies
        $this->customerSessionMock = $this->createMock(CustomerSession::class);
        $this->wishlistFactoryMock = $this->createMock(WishlistFactory::class);

        // Create additional mock objects required for ExplicitDataService constructor
        $viewedProductsBlockMock = $this->createMock(\Magento\Reports\Block\Product\Viewed::class);
        $orderCollectionFactoryMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\CollectionFactory::class);
        $productRepositoryMock = $this->createMock(\Magento\Catalog\Model\ProductRepository::class);
        $checkoutSessionMock = $this->createMock(\Magento\Checkout\Model\Session::class);
        $extraPersonalizationHelperMock = $this->createMock(\PersonalizeAI\SmartRecommend\Helper\ExtraPersonalization::class);

        // Instantiate ExplicitDataService with mocked dependencies
        $this->explicitDataService = new ExplicitDataService(
            $this->customerSessionMock,
            $viewedProductsBlockMock,
            $orderCollectionFactoryMock,
            $productRepositoryMock,
            $checkoutSessionMock,
            $this->wishlistFactoryMock,
            $extraPersonalizationHelperMock
        );
    }

    /**
     * Test getWishlistItems for a logged out customer
     */
    public function testGetWishlistItemsForLoggedOutCustomer()
    {
        // Configure customer session mock to simulate logged out state
        $this->customerSessionMock->method('isLoggedIn')->willReturn(false);
        
        $result = $this->explicitDataService->getWishlistItems();
        
        // Assert that the result is empty for a logged out customer
        $this->assertEmpty($result);
    }

    /**
     * Test getWishlistItems for a logged in customer with items in wishlist
     */
    public function testGetWishlistItemsForLoggedInCustomer()
    {
        // Configure customer session mock to simulate logged in state
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMock->method('getCustomerId')->willReturn(1);

        // Create and configure mocks for Wishlist, WishlistItem, and Product
        $wishlistMock = $this->createMock(Wishlist::class);
        $wishlistItemMock = $this->createMock(WishlistItem::class);
        $productMock = $this->createMock(Product::class);

        // Configure product mock
        $productMock->method('getId')->willReturn(1);
        $productMock->method('getName')->willReturn('Test Product');
        $productMock->method('getSku')->willReturn('TEST-SKU');
        $productMock->method('getFinalPrice')->willReturn(9.99);

        // Configure wishlist item mock to return the product mock
        $wishlistItemMock->method('getProduct')->willReturn($productMock);

        // Create a mock collection that is iterable
        $itemCollectionMock = $this->createMock(\Magento\Wishlist\Model\ResourceModel\Item\Collection::class);
        $itemCollectionMock->method('getIterator')
            ->willReturn(new \ArrayIterator([$wishlistItemMock]));

        // Configure wishlist mock
        $wishlistMock->method('getItemCollection')->willReturn($itemCollectionMock);
        $wishlistMock->method('loadByCustomerId')->willReturnSelf();

        // Configure wishlist factory mock to return the wishlist mock
        $this->wishlistFactoryMock->method('create')->willReturn($wishlistMock);

        // Define the expected result
        $expectedResult = [
            [
                'id' => 1,
                'name' => 'Test Product',
                'sku' => 'TEST-SKU',
                'price' => 9.99,
            ]
        ];

        // Call the method under test
        $result = $this->explicitDataService->getWishlistItems();

        // Assert that the result matches the expected output
        $this->assertEquals($expectedResult, $result);
    }
}
