<?php
namespace PersonalizeAI\SmartRecommend\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use PersonalizeAI\SmartRecommend\Model\ExplicitDataService;
use PersonalizeAI\SmartRecommend\Helper\ExtraPersonalization;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Reports\Block\Product\Viewed as ViewedProductsBlock;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Wishlist\Model\WishlistFactory;

/**
 * Test case for the getPrompt method of ExplicitDataService
 *
 * Run test:
 * sudo ./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist
 * --no-extensions app/code/PersonalizeAI/SmartRecommend/Test/Unit/Model/PromptTest.php
 */
class PromptTest extends TestCase
{
    /**
     * @var ExplicitDataService
     */
    protected $explicitDataService;

    /**
     * @var ExtraPersonalization|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $extraPersonalizationHelperMock;

    /**
     * Set up the test environment before each test
     */
    protected function setUp(): void
    {
        // Create mocks for the dependencies
        $this->extraPersonalizationHelperMock = $this->createMock(ExtraPersonalization::class);
        $customerSessionMock = $this->createMock(CustomerSession::class);
        $viewedProductsBlockMock = $this->createMock(ViewedProductsBlock::class);
        $orderCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $productRepositoryMock = $this->createMock(ProductRepository::class);
        $checkoutSessionMock = $this->createMock(CheckoutSession::class);
        $wishlistFactoryMock = $this->createMock(WishlistFactory::class);

        // Create a partial mock of ExplicitDataService
        $this->explicitDataService = $this->getMockBuilder(ExplicitDataService::class)
            ->setConstructorArgs([
                $customerSessionMock,
                $viewedProductsBlockMock,
                $orderCollectionFactoryMock,
                $productRepositoryMock,
                $checkoutSessionMock,
                $wishlistFactoryMock,
                $this->extraPersonalizationHelperMock
            ])
            ->setMethods(['getViewedProducts', 'getBoughtProducts', 'getCartItems', 'getWishlistItems'])
            ->getMock();
    }

    /**
     * Test the getPrompt method
     */
    public function testGetPrompt()
    {
        // Configure the ExtraPersonalization helper mock
        $this->extraPersonalizationHelperMock->method('getExtraPersonalizationPrompts')
            ->willReturn([
                'viewed_items' => true,
                'bought_items' => true,
                'cart_items' => true,
                'wish_list' => true
            ]);

        // Set expectations for the mocked methods
        $this->explicitDataService->expects($this->once())
            ->method('getViewedProducts')
            ->willReturn([
                ['name' => 'Viewed Product 1', 'sku' => 'VP1'],
                ['name' => 'Viewed Product 2', 'sku' => 'VP2']
            ]);

        $this->explicitDataService->expects($this->once())
            ->method('getBoughtProducts')
            ->willReturn([
                ['name' => 'Bought Product 1'],
                ['name' => 'Bought Product 2']
            ]);

        $this->explicitDataService->expects($this->once())
            ->method('getCartItems')
            ->willReturn([
                ['name' => 'Cart Product 1'],
                ['name' => 'Cart Product 2']
            ]);

        $this->explicitDataService->expects($this->once())
            ->method('getWishlistItems')
            ->willReturn([
                ['name' => 'Wishlist Product 1'],
                ['name' => 'Wishlist Product 2']
            ]);

        // Call the method under test
        $result = $this->explicitDataService->getPrompt();

        // Assert that the result contains the expected product information
        $this->assertStringContainsString("Recently viewed items: Viewed Product 1, Viewed Product 2", $result);
        $this->assertStringContainsString("Previously bought items: Bought Product 1, Bought Product 2", $result);
        $this->assertStringContainsString("Items currently in cart: Cart Product 1, Cart Product 2", $result);
        $this->assertStringContainsString("Items in wishlist: Wishlist Product 1, Wishlist Product 2", $result);
    }
}
