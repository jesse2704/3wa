<?php
namespace PersonalizeAI\SmartRecommend\Plugin;

/**
 * Class CustomerIdContext
 *
 * This class is responsible for managing the customer ID context
 * in the HTTP context for Magento.
 */
class CustomerIdContext
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * CustomerIdContext constructor.
     *
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Http\Context $httpContext
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Http\Context $httpContext
    ) {
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
    }

    /**
     * Before execute plugin for setting the current customer ID in HTTP context.
     *
     * @param \Magento\Framework\App\Action\AbstractAction $subject
     */
    public function beforeExecute(\Magento\Framework\App\Action\AbstractAction $subject)
    {
        // Get the customer ID from the session and set it in the HTTP context
        $customerId = $this->customerSession->getCustomerId();
        $this->httpContext->setValue('current_customer_id', $customerId, false);
    }
}
