<?php
namespace PersonalizeAI\SmartRecommend\Model;

/**
 * Class QuestionConfig
 *
 * This class manages the configuration of questions for personalization.
 */
class QuestionConfig
{
    // Define question keys as constants
    public const Q_1 = 'productPreference';
    public const Q_2 = 'priceSensitivity';
    public const Q_3 = 'stylePreference';
    public const Q_4 = 'hobbies';
    public const Q_5 = 'purchaseFrequency';

    /**
     * @var array Define the order of questions
     */
    private $questionOrder = [
        self::Q_1,
        self::Q_2,
        self::Q_3,
        self::Q_4,
        self::Q_5
    ];

    /**
     * @var array Define question data
     */
    private $questionData = [
        self::Q_1 => [
            "title" => "Wat zijn jouw productvoorkeuren?",
            "options" => [
                ["choice" => "electronics", "text" => "Elektronica"],
                ["choice" => "clothing", "text" => "Kleding"],
            ],
        ],
        self::Q_2 => [
            "title" => "Wat is jouw prijsgevoeligheid?",
            "options" => [
                ["choice" => "budget", "text" => "Budgetvriendelijke opties"],
                ["choice" => "premium", "text" => "Premium producten"],
            ],
        ],
        self::Q_3 => [
            "title" => "Welke stijl past het beste bij jou?",
            "options" => [
                ["choice" => "modern", "text" => "Modern en strak"],
                ["choice" => "classic", "text" => "Klassiek en tijdloos"],
            ],
        ],
        self::Q_4 => [
            "title" => "Waar besteed je je vrije tijd het liefst aan?",
            "options" => [
                ["choice" => "sports", "text" => "Sporten en outdoor activiteiten"],
                ["choice" => "reading", "text" => "Lezen en culturele activiteiten"],
            ],
        ],
        self::Q_5 => [
            "title" => "Hoe vaak doe je online aankopen?",
            "options" => [
                ["choice" => "weekly", "text" => "Wekelijks"],
                ["choice" => "monthly", "text" => "Maandelijks"],
            ],
        ],
    ];

    /**
     * Get question data.
     *
     * @return array
     */
    public function getQuestionData(): array
    {
        return $this->questionData;
    }

    /**
     * Get ordered questions with additional metadata.
     *
     * @return array
     */
    public function getQuestions(): array
    {
        $questions = [];
        
        foreach ($this->questionOrder as $index => $key) {
            if (isset($this->questionData[$key])) {
                // Use a single array merge to create the question entry
                $questions[$key] = array_merge(
                    $this->questionData[$key],
                    [
                        'id' => 'question' . ($index + 1),
                        'key' => $key,
                        'dataQuestion' => $key,
                    ]
                );
            }
        }
        
        return $questions;
    }

    /**
     * Get the keys for the questions.
     *
     * @return array
     */
    public function getQuestionKeys(): array
    {
        return $this->questionOrder;
    }
}
