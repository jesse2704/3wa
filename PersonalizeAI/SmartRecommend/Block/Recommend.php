<?php

declare(strict_types=1);

namespace PersonalizeAI\SmartRecommend\Block;

use Magento\Framework\View\Element\Template;
use PersonalizeAI\SmartRecommend\Model\QuestionConfig;
use Magento\Framework\App\Http\Context;

class Recommend extends Template
{
    protected $httpContext;

    /**
     * @var QuestionConfig
     */
    private $questionConfig;

    /**
     * @param Template\Context $context
     * @param QuestionConfig $questionConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        QuestionConfig $questionConfig,
        Context $httpContext,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        $this->questionConfig = $questionConfig;
        parent::__construct($context, $data);
    }

    /**
     * Get questions from the question config
     *
     * @return array
     */
    public function getQuestions(): array
    {
        return $this->questionConfig->getQuestions();
    }

    /**
     * Get question keys from the question config
     *
     * @return array
     */
    public function getQuestionKeys(): array
    {
        return $this->questionConfig->getQuestionKeys();
    }

    public function getAnwsers(): array
    {
        return $this->questionConfig->getQuestionData();
    }


    public function getQuestionTitles(): array
    {
        $questionData = $this->questionConfig->getQuestionData();
        $titles = [];

        foreach ($questionData as $key => $question) {
            if (isset($question['title'])) {
                $titles[$key] = $question['title'];
            }
        }

        return $titles;
    }

    public function getChoiceTextMappings(): array
    {
        $questionData = $this->getQuestionData();
        $mappings = [];

        foreach ($questionData as $key => $question) {
            if (isset($question['options'])) {
                $mappings[$key] = [];
                foreach ($question['options'] as $option) {
                    if (isset($option['choice']) && isset($option['text'])) {
                        $mappings[$key][$option['choice']] = $option['text'];
                    }
                }
            }
        }

        return $mappings;
    }

    public function getQuestionData(): array
    {
        return $this->questionConfig->getQuestionData();
    }

    public function getQuestionnaireResponses(): array
    {
        $responses = [];

        foreach ($this->getQuestionKeys() as $key) {
            $value = $this->getRequest()->getParam($key);
            if ($value !== null) {
                $responses[$key] = $value;
            }
        }

        return $responses;
    }

    

    public function getUserToken()
    {
        $customerId = $this->httpContext->getValue('current_customer_id');
        return $customerId ? 'user_' . $customerId : 'guest_' . uniqid();
    }
}
