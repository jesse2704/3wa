<?php
namespace Personalizer\ProductImage\Block\Product\View;

use CURLFile;
use Magento\Framework\DataObject;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Model\Product;

class Gallery extends \Magento\Catalog\Block\Product\View\Gallery
{
    protected $generetedUrl;
    protected $additionalImageAdded = false;
    /**
     * Get additional images for the gallery
     *
     * @return array
     */
    public function getGalleryImages()
    {
        $galleryImages = parent::getGalleryImages();
        if (!$this->additionalImageAdded) {
            $objectManager = ObjectManager::getInstance();
            $cookieManager = $objectManager->get(\Magento\Framework\Stdlib\CookieManagerInterface::class);
            if ($cookieManager->getCookie('personalize_accepted') && $this->getModuleEnable() == 1 && $this->getApiKey()) {

                $mediaPath = $this->getMediaDir();
                $firstImage = $galleryImages->getFirstItem();
                $imagePath = $this->img2Img($mediaPath, $firstImage->getData('file'));

                // Add your additional image to the collection
                if ($imagePath) {
                    $data = [
                        'value_id' => uniqid(),
                        'file' => $imagePath,
                        'media_type' => 'image',
                        'path' => '/' . $imagePath,
                        'small_image_url' => '/' . $imagePath,
                        'medium_image_url' => '/' . $imagePath,
                        'large_image_url' => '/' . $imagePath,
                        'label' => "",
                        'position' => 999,
                    ];

                    $additionalImageObject = new DataObject($data);
                    $galleryImages->addItem($additionalImageObject);

                    $this->additionalImageAdded = true;
                }
            }
        }
        return $galleryImages;

    }

    /**
     * Media directory name for the temporary file storage
     * pub/media/
     *
     * @return string
     */
    protected function getMediaDir()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $directory = $objectManager->get(\Magento\Framework\App\Filesystem\DirectoryList::class);

        return $directory->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'catalog' . DIRECTORY_SEPARATOR . 'product';
    }

    public function getGalleryImagesJson()
    {
        $imagesItems = [];
        /** @var DataObject $image */
        foreach ($this->getGalleryImages() as $image) {
            $mediaType = $image->getMediaType();
            if ($image->getData('position') == 999) {
                $imageItem = new DataObject(
                    [
                        'thumb' => $this->getCustomUrl(),
                        'img' => $this->getCustomUrl(),
                        'full' => $this->getCustomUrl(),
                        'caption' => $image->getLabel() ?: $this->getProduct()->getName(),
                        'position' => $image->getData('position'),
                        'isMain' => $this->isMainImage($image),
                        'type' => $mediaType !== null ? str_replace('external-', '', $mediaType) : '',
                        'videoUrl' => $image->getVideoUrl(),
                    ]
                );
            } else {
                $imageItem = new DataObject(
                    [
                        'thumb' => $image->getData('small_image_url'),
                        'img' => $image->getData('medium_image_url'),
                        'full' => $image->getData('large_image_url'),
                        'caption' => $image->getLabel() ?: $this->getProduct()->getName(),
                        'position' => $image->getData('position'),
                        'isMain' => $this->isMainImage($image),
                        'type' => $mediaType !== null ? str_replace('external-', '', $mediaType) : '',
                        'videoUrl' => $image->getVideoUrl(),
                    ]
                );
            }

            $imagesItems[] = $imageItem->toArray();
        }
        if (empty($imagesItems)) {
            $imagesItems[] = [
                'thumb' => $this->_imageHelper->getDefaultPlaceholderUrl('thumbnail'),
                'img' => $this->_imageHelper->getDefaultPlaceholderUrl('image'),
                'full' => $this->_imageHelper->getDefaultPlaceholderUrl('image'),
                'caption' => '',
                'position' => '0',

                'isMain' => true,
                'type' => 'image',
                'videoUrl' => null,
            ];
        }
        return json_encode($imagesItems);
    }

    public function getCustomUrl(){
        return $this->generetedUrl;
    }

    public function img2Img($filePath, $file) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cookieManager = $objectManager->get(\Magento\Framework\Stdlib\CookieManagerInterface::class);
        $fileClass = $objectManager->get(\Magento\Framework\Filesystem\Io\File::class);
        // Function to resize image using GD library
        function resizeImage($filePath, $filename, $newWidth, $newHeight)
        {
            // Get new dimensions
            list($width, $height) = getimagesize($filename);
            $imageResized = imagecreatetruecolor($newWidth, $newHeight);
            $imageType = exif_imagetype($filename);
            if ($imageType === IMAGETYPE_JPEG) {
                $imageTmp = imagecreatefromjpeg($filename);
            } elseif ($imageType === IMAGETYPE_PNG) {
                $imageTmp = imagecreatefrompng($filename);
            } else {
            $imageTmp = null;
        }

            imagecopyresampled($imageResized, $imageTmp, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Save resized image to file
            imagejpeg($imageResized, '/' . $filePath . '/nieuwe_afbeelding.jpg');
            imagedestroy($imageResized);
            imagedestroy($imageTmp);
        }
        // Resize the image
        resizeImage($filePath, '' . $filePath . $file, 1024, 1024);

        // Create form data
        $formData = [
            'steps' => 30,
            'sampler' => 'dpmpp_2m_karras',
            'init_image' => new CURLFile($filePath . '/nieuwe_afbeelding.jpg', 'image/jpeg', 'image.jpg'),
            'prompt' => 'Personalize this product for someone who does like: '. $cookieManager->getCookie('personalize_accepted') . ' (please note the following: ' . $this->getApiInstruction() . ')',
            'negative_prompt' => 'ugly, tiling, poorly drawn hands, poorly drawn feet, poorly drawn face, out of frame, extra limbs, disfigured, deformed, body out of frame, blurry, bad anatomy, blurred, watermark, grainy, signature, cut off, draft, change object, change color of object',
            'seed' => '',
            'upscale' => 1,
            'format' => 'png',
            'guidance' => 9,
            'image_guidance' => 2.5,
        ];

        // Initialize cURL
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, 'https://api.dezgo.com/edit-image');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: */*',
            'X-Dezgo-Key: '. $this->getApiKey(),
            'Content-Type: multipart/form-data'
        ]);


        // Execute cURL request
        $response = curl_exec($ch);

        // Check for errors
        if ($response === false) {
            throw new Exception(curl_error($ch));
        }

        // Close cURL
        curl_close($ch);

        // Save raw PNG data to a file
        $file_path = $filePath . '/output.png';
        file_put_contents($file_path, $response);

        // Create an image resource from the saved file
        $image = imagecreatefrompng($file_path);

        // Save images
        $newFileName = $filePath . "/output.png";


        /** add saved file to the $product gallery */
        $result = $this->getProduct()->addImageToMediaGallery($newFileName, 'image', false, false);
        $this->generetedUrl = '/pub/media/catalog/product/' . basename("/output.png");

        return $result ? $newFileName : null;
    }

    public function getModuleEnable()
    {
        $objectManager = ObjectManager::getInstance();
        $scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        return $scopeConfig->getValue('productimage/general/enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getApiInstruction()
    {
        $objectManager = ObjectManager::getInstance();
        $scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        return $scopeConfig->getValue('productimage/general/instruction', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    private function getApiKey()
    {
        $objectManager = ObjectManager::getInstance();
        $scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        return $scopeConfig->getValue('productimage/general/apikey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}

