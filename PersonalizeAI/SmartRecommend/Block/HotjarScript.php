<?php

declare(strict_types=1);

namespace PersonalizeAI\SmartRecommend\Block;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;

class HotjarScript extends Template
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Context
     */
    private $httpContext;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param Template\Context $context
     * @param Session $customerSession
     * @param Registry $registry
     * @param Context $httpContext
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $customerSession,
        Registry $registry,
        Context $httpContext,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->registry = $registry;
        $this->httpContext = $httpContext;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context, $data);
    }

    /**
     * Get customer ID
     *
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        $customerId = $this->httpContext->getValue('current_customer_id')
            ?: $this->customerSession->getCustomerId();
        
        // Convert to integer or null
        return $customerId !== null ? (int)$customerId : null;
    }
    
    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH) ?: $this->customerSession->isLoggedIn();
    }
}
