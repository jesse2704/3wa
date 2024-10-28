<?php
namespace PersonalizeAI\SmartRecommend\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ExtraPersonalization
 *
 * Helper class for managing extra personalization settings and prompts.
 */
class ExtraPersonalization extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * ExtraPersonalization constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Get extra personalization prompts based on admin configuration settings.
     *
     * @return array
     */
    public function getExtraPersonalizationPrompts()
    {
        $prompts = [];

        $options = [
            'viewed_items' => 'Keep in mind the user has previously viewed these items.',
            'bought_items' => 'Keep in mind the user has previously bought these items.',
            'cart_items' => 'Keep in mind the user has these items in their shopping cart.',
            'wish_list' => 'Take into account the items in the user\'s wish list.',
            'category_browsing' => 'Consider the categories the user has been browsing.'
        ];

        foreach ($options as $key => $prompt) {
            if ($this->scopeConfig->isSetFlag(
                'smartrecommend_settings/extra_personalization/' . $key,
                ScopeInterface::SCOPE_STORE
            )) {
                $prompts[$key] = $prompt;
            }
        }

        return $prompts;
    }
}