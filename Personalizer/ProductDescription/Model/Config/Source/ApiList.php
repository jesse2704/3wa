<?php
namespace Personalizer\ProductDescription\Model\Config\Source;
class ApiList implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'frontend', 'label' => __('Frontend')],
            ['value' => 'backend', 'label' => __('Backend')]
        ];
    }
}

