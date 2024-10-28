<?php
namespace PersonalizeAI\SmartRecommend\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use PersonalizeAI\SmartRecommend\Model\ExplicitDataService;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Catalog\Model\Product;

/**
 * Test case for the getBoughtProducts method of ExplicitDataService
 *
 * run test: "sudo ./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist --no-extensions
 * app/code/PersonalizeAI/SmartRecommend/Test/Unit/Model/BoughtProductsTest.php"
 */

class BoughtProductsTest extends TestCase
{
    /**
     * @var ExplicitDataService
     */
    protected $explicitDataService;

    /**
     * @var CustomerSession|MockObject
     */
    protected $customerSessionMock;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $orderCollectionFactoryMock;

    /**
     * @var ProductRepository|MockObject
     */
    protected $productRepositoryMock;

    /**
     * Set up the test environment before each test
     */
    protected function setUp(): void
    {
        // Create mocks for the main dependencies
        $this->customerSessionMock = $this->createMock(CustomerSession::class);
        $this->orderCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->productRepositoryMock = $this->createMock(ProductRepository::class);

        // Create additional mock objects required for ExplicitDataService constructor
        $viewedProductsBlockMock = $this->createMock(\Magento\Reports\Block\Product\Viewed::class);
        $checkoutSessionMock = $this->createMock(\Magento\Checkout\Model\Session::class);
        $wishlistFactoryMock = $this->createMock(\Magento\Wishlist\Model\WishlistFactory::class);
        $extraPersonalizationHelperMock = $this->createMock(\PersonalizeAI\SmartRecommend\Helper\ExtraPersonalization::class);

        // Instantiate the ExplicitDataService with mock objects
        $this->explicitDataService = new ExplicitDataService(
            $this->customerSessionMock,
            $viewedProductsBlockMock,
            $this->orderCollectionFactoryMock,
            $this->productRepositoryMock,
            $checkoutSessionMock,
            $wishlistFactoryMock,
            $extraPersonalizationHelperMock
        );
    }

    /**
     * Test getBoughtProducts for a logged out customer
     */
    public function testGetBoughtProductsForLoggedOutCustomer()
    {
        // Mock customer session to return false for isLoggedIn
        $this->customerSessionMock->method('isLoggedIn')->willReturn(false);
        
        $result = $this->explicitDataService->getBoughtProducts();
        
        // Assert that the result is empty for a logged out customer
        $this->assertEmpty($result);
    }

    /**
     * Test getBoughtProducts for a logged in customer with no customer ID
     */
    public function testGetBoughtProductsForLoggedInCustomerWithNoCustomerId()
    {
        // Mock customer session to return true for isLoggedIn but null for getCustomerId
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMock->method('getCustomerId')->willReturn(null);
        
        $result = $this->explicitDataService->getBoughtProducts();
        
        // Assert that the result is empty when there's no customer ID
        $this->assertEmpty($result);
    }

    /**
     * Test getBoughtProducts for a logged in customer with orders
     */
    public function testGetBoughtProductsForLoggedInCustomerWithOrders()
    {
        // Mock customer session for a logged in customer with ID 1
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMock->method('getCustomerId')->willReturn(1);

        // Create and configure order mock
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getIncrementId')->willReturn('000000001');
        $orderMock->method('getCreatedAt')->willReturn('2024-09-01 12:00:00');
        $orderMock->method('getStatus')->willReturn('complete');

        // Create and configure order item mock
        $orderItemMock = $this->createMock(OrderItem::class);
        $orderItemMock->method('getProductId')->willReturn(1);
        $orderItemMock->method('getPrice')->willReturn(9.99);
        $orderItemMock->method('getQtyOrdered')->willReturn(2);

        $orderMock->method('getAllVisibleItems')->willReturn([$orderItemMock]);

        // Create an iterator for the order collection
        $orderIterator = new \ArrayIterator([$orderMock]);

        // Configure order collection mock
        $orderCollectionMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $orderCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $orderCollectionMock->method('getIterator')->willReturn($orderIterator);

        $this->orderCollectionFactoryMock->method('create')->willReturn($orderCollectionMock);

        // Create and configure product mock
        $productMock = $this->createMock(Product::class);
        $productMock->method('getId')->willReturn(1);
        $productMock->method('getSku')->willReturn('TEST-SKU');
        $productMock->method('getName')->willReturn('Test Product');

        $this->productRepositoryMock->method('getById')->willReturn($productMock);

        // Define the expected result
        $expectedResult = [
            [
                'id' => 1,
                'sku' => 'TEST-SKU',
                'name' => 'Test Product',
                'price' => 9.99,
                'qty' => 2,
                'order_id' => '000000001',
                'order_date' => '2024-09-01 12:00:00',
                'order_status' => 'complete'
            ]
        ];

        // Call the method under test
        $result = $this->explicitDataService->getBoughtProducts();

        // Assert that the result matches the expected output
        $this->assertEquals($expectedResult, $result);
    }
}
