<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Traits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Image\Image;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Component\ComponentHelper;
use JoomShaper\Component\EasyStore\Administrator\Model\MediaModel;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

trait Media
{
    /**
     * Manage Media Request Method
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function media()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['PUT', 'PATCH'], $requestMethod);

        switch ($requestMethod) {
            case 'GET':
                $this->getImages();
                break;
            case 'POST':
                $this->upload();
                break;
            case 'DELETE':
                $this->deleteImage();
        }
    }

    /**
     * Clear the temporary images Endpoint.
     *
     * @return  void
     * @since   4.1.0
     */
    public function clearTemporaryImages()
    {
        $requestMethod = $this->getInputMethod();
        $this->checkNotAllowedMethods(['POST', 'PUT', 'PATCH', 'DELETE'], $requestMethod);

        $clientId   = $this->getInput('client_id', '', 'STRING');
        $imageModel = new MediaModel();
        $response   = new \stdClass();

        $response->status = false;

        if ($imageModel->clearTemporaryImages($clientId)) {
            $this->removeTemporaryFiles($clientId);
            $response->status = true;
        }

        $this->sendResponse($response);
    }

    /**
     * Remove the temporary files from the filesystem.
     *
     * @param   string  $clientId   The client id.
     *
     * @return  void
     * @since   4.1.0
     */
    protected function removeTemporaryFiles(string $clientId)
    {
        $mediaParams = ComponentHelper::getParams('com_media');
        $directory   = '/tmp/' . $clientId;
        $folder      = $mediaParams->get('file_path', 'images') . '/easystore' . $directory;

        if (\file_exists($folder)) {
            Folder::delete($folder);
        }
    }

    /**
     * Manage Image Ordering Request Method
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function mediaOrdering()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['GET', 'PUT', 'PATCH', 'DELETE'], $requestMethod);

        if ($requestMethod === 'POST') {
            $this->orderImages();
        }
    }

    /**
     * Get images list from the images/temp_images tables.
     *
     *
     * @return  void
     * @since   1.0.0
     */
    public function getImages()
    {
        $productId = $this->getInput('product_id', null, 'INT');
        $clientId  = $this->getInput('client_id', '', 'STRING');

        $isTemporary       = empty($productId);
        $productOrClientId = $isTemporary ? $clientId : $productId;

        $response = [];

        $imageModel = new MediaModel();
        $response   = $imageModel->getImages($productOrClientId, $isTemporary);

        $this->sendResponse($response);
    }

    private function getMediaType($fileName)
    {
        $mediaParams     = ComponentHelper::getParams('com_media');
        $imageExtensions = $mediaParams->get('image_extensions', '');
        $videoExtensions = $mediaParams->get('video_extensions', '');
        $imageExtensions = explode(',', $imageExtensions);
        $videoExtensions = explode(',', $videoExtensions);

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if (in_array($extension, $imageExtensions, true)) {
            return 'image';
        }

        if (in_array($extension, $videoExtensions, true)) {
            return 'video';
        }

        return 'unsupported';
    }

    /**
     * Upload Function
     *
     * @return void
     */
    public function upload()
    {
        $acl       = AccessControl::create();
        $input     = Factory::getApplication()->input;
        $files     = $input->files->get('product_images');
        $productId = $this->getInput('product_id', null, 'INT');
        $clientId  = $this->getInput('client_id', '', 'STRING');

        $hasPermission = $acl->canCreate()
            || $acl->canEdit()
            || $acl->setContext('product')->canEditOwn($productId);

        if (!$hasPermission) {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_PERMISSION_ERROR_MSG")], 403);
        }

        $isTemporary       = empty($productId);
        $productOrClientId = $isTemporary ? $clientId : $productId;

        $images = [];

        $imageModel       = new MediaModel();
        $hasFeaturedImage = $imageModel->hasFeaturedImage($productOrClientId, $isTemporary);
        $ordering         = $imageModel->getMaximumOrdering($productOrClientId, $isTemporary);

        foreach ($files as $key => $file) {
            $isValid = EasyStoreHelper::isValid($file);

            if (!$isValid->status) {
                $this->output[] = $isValid;

                continue;
            }

            $folder    = $this->createUploadFolder($clientId, $productId);
            $mediaFile = preg_replace('@\s+@', "-", File::makeSafe(basename(strtolower($file['name']))));
            $baseName  = File::stripExt($mediaFile);
            $ext       = pathinfo($mediaFile, PATHINFO_EXTENSION);
            $mediaName = $baseName . '.' . $ext;
            $dest      = JPATH_ROOT . '/' . $folder . '/' . $mediaName;
            $src       = $folder . '/' . $mediaName;
            $mediaData = new \stdClass();
            $response  = new \stdClass();
            $mediaType = $this->getMediaType($mediaFile);

            $image = new \stdClass();

            if (File::upload($file['tmp_name'], $dest, false, true)) {
                $mediaWidth  = 0;
                $mediaHeight = 0;

                if ($mediaType === 'image') {
                    if ($ext === 'svg') {
                        $image = $this->svgGetimagesize($dest);
                    } else {
                        $image = Image::getImageFileProperties($dest);
                    }

                    $mediaWidth  = $image->width;
                    $mediaHeight = $image->height;
                } else {
                    $mediaWidth  = 0;
                    $mediaHeight = 0;
                }

                $mediaData->name   = $mediaName;
                $mediaData->type   = $mediaType;
                $mediaData->width  = $mediaWidth;
                $mediaData->height = $mediaHeight;
                $mediaData->src    = $src;

                if ($isTemporary) {
                    $mediaData->client_id = $clientId;
                } else {
                    $mediaData->product_id = $productId;
                }

                $mediaData->is_featured = $hasFeaturedImage ? 0 : ($key === 0 ? 1 : 0);
                $mediaData->ordering    = $ordering + $key + 1;
                $mediaData->alt_text    = $mediaName;
                $mediaData->language    = '*';
                $images[]               = $mediaData;

                $response->status  = true;
                $response->message = Text::_('COM_EASYSTORE_APP_PRODUCT_IMAGE_UPLOADED');
            } else {
                $response->status  = false;
                $response->message = Text::_('COM_EASYSTORE_APP_PRODUCT_IMAGE_UPLOAD_FAILED');
            }

            $this->output[] = $response;
        }

        if (!empty($images)) {
            $imageModel->store($images, $isTemporary);
        }

        $this->sendResponse($this->output);
    }

    /**
     * Store Logo upload Api
     *
     * @return void
     */
    public function storeLogo()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['GET', 'PUT', 'PATCH', 'DELETE'], $requestMethod);

        $response     = new \stdClass();
        $input        = Factory::getApplication()->input;
        $file         = $input->files->get('store_logo');
        $uploadStatus = false;
        $src          = '';

        $isValid = EasyStoreHelper::isValid($file);

        if (!$isValid->status) {
            $response->status  = false;
            $response->message = Text::_('COM_EASYSTORE_APP_PRODUCT_IMAGE_UPLOAD_FAILED');

            $this->sendResponse($response);
        }

        $filePath = $this->createStoreLogoPath();

        if (empty($filePath->dest)) {
            $response->status  = false;
            $response->message = Text::_('COM_EASYSTORE_APP_PRODUCT_IMAGE_UPLOAD_FAILED');

            $this->sendResponse($response);
        }

        if (File::upload($file['tmp_name'], $filePath->dest, false, true)) {
            $uploadStatus = true;
            $src          = $filePath->src;

            $this->settingsDataUpdate($src);
        }

        $response = new \stdClass();

        if ($uploadStatus) {
            $response->status  = true;
            $response->message = Text::_('COM_EASYSTORE_APP_STORE_LOGO_UPLOADED');
            $response->src     = $src;
        } else {
            $response->status  = false;
            $response->message = Text::_('COM_EASYSTORE_APP_STORE_LOGO_UPLOAD_FAILED');
        }

        $this->sendResponse($response);
    }

    /**
     * Function to create the destination path for store logo
     *
     * @return object
     */
    public function createStoreLogoPath()
    {
        $input = Factory::getApplication()->input;
        $file  = $input->files->get('store_logo');

        if (empty($file)) {
            return '';
        }

        $mediaParams = ComponentHelper::getParams('com_media');
        $directory   = '/store-logo';
        $folder      = $mediaParams->get('file_path', 'images') . '/easystore' . $directory;

        $mediaFile = preg_replace('@\s+@', "-", File::makeSafe(basename(strtolower($file['name']))));
        $baseName  = File::stripExt($mediaFile);
        $ext       = pathinfo($mediaFile, PATHINFO_EXTENSION);
        $mediaName = $baseName . '.' . $ext;

        $response       = new \stdClass();
        $response->dest = JPATH_ROOT . '/' . Path::clean($folder) . '/' . Path::clean($mediaName);
        $response->src  = Uri::root(true) . '/' . Path::clean($folder) . '/' . Path::clean($mediaName);

        return $response;
    }

    /**
     * Function to update new uploaded image on settings table
     *
     * @param string $newSrc
     * @return void
     */
    public function settingsDataUpdate(string $newSrc)
    {
        $oldSrc = SettingsHelper::getSettings()->get('general.storeLogo', '');

        if (!empty($oldSrc) && (trim($oldSrc) != trim($newSrc))) {
            $this->removeImage($oldSrc);
        }

        SettingsHelper::setSettings('general.storeLogo', $newSrc);
    }

    /**
     * Delete Images from DB and Files
     *
     * @return void
     */
    public function deleteImage()
    {
        $acl = AccessControl::create();

        if (!$acl->canDelete()) {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_PERMISSION_ERROR_MSG")], 403);
        }

        $productId = $this->getInput('product_id', null, 'INT');
        $clientId  = $this->getInput('client_id', '', 'STRING');
        $ids       = $this->getInput('ids', '', 'STRING');
        $ids       = !empty($ids) ? explode(',', $ids) : [];

        $isTemporary = empty($productId);

        $columns = ['id', 'src'];
        $orm     = new EasyStoreDatabaseOrm();

        if ($isTemporary) {
            $images = $orm->setColumns($columns)
                ->hasMany($clientId, '#__easystore_temp_media', 'client_id');
        } else {
            $images = $orm->setColumns($columns)
                ->hasMany($productId, '#__easystore_media', 'product_id');
        }

        if (!empty($ids)) {
            $images = $orm->updateQuery(function ($query) use ($orm, $ids) {
                $query->where($orm->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
            });
        }

        $images = $orm->loadObjectList();

        $response = new \stdClass();

        if (empty($images)) {
            $response->status = false;

            $this->sendResponse($response);
        }

        $imageId  = [];
        $imageSrc = [];

        foreach ($images as $image) {
            $imageId[]  = $image->id;
            $imageSrc[] = $image->src;
        }

        $imageModel = new MediaModel();

        if ($imageModel->deleteImages($imageId, $isTemporary)) {
            foreach ($imageSrc as $src) {
                $this->removeImage($src);
            }

            if ($isTemporary) {
                $imageModel->refreshFeaturedImage($clientId, $isTemporary);
            } else {
                $imageModel->refreshFeaturedImage($productId, $isTemporary);
            }

            $response->status = true;
        } else {
            $response->status = false;
        }

        $this->sendResponse($response);
    }

    /**
     * Create Upload Folder for temporary image upload.
     *
     * @param string $uniqueId  Unique Id
     * @param int $productId    Product Id
     *
     * @return string
     */
    private function createUploadFolder($clientId, $productId): string
    {
        $mediaParams = ComponentHelper::getParams('com_media');
        $directory   = $productId ? '/product-' . $productId : '/tmp/' . $clientId;
        $folder      = $mediaParams->get('file_path', 'images') . '/easystore' . $directory;
        $imagePath   = JPATH_ROOT . '/' . $folder;

        if (!file_exists($imagePath)) {
            Folder::create($imagePath, 0755);
        }

        return $folder;
    }

    /**
     * Remove the file from the filesystem.
     *
     * @param   string  $src   The src path.
     *
     * @return  void
     * @since   1.0.0
     */
    private function removeImage(string $src)
    {
        $src = JPATH_ROOT . '/' . $src;

        if (\file_exists($src)) {
            File::delete($src);
        }
    }

    /**
     * Function for ordering images
     *
     * @return bool
     */
    private function orderImages()
    {
        $productId = $this->getInput('product_id', null, 'INT');
        $clientId  = $this->getInput('client_id', '', 'STRING');
        $ordering  = $this->getInput('ordering', [], 'ARRAY');
        $response  = new \stdClass();

        if (empty($ordering) || (empty($productId) && empty($clientId))) {
            $response->status  = false;
            $response->message = Text::_('COM_EASYSTORE_APP_PRODUCT_IMAGE_ORDERING_FAILED');

            $this->sendResponse($response);
        } else {
            $imageModel = new MediaModel();
            $mediaData  = new \stdClass();

            $mediaData->product_id = $productId;
            $mediaData->client_id  = $clientId;

            foreach ($ordering as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                $value               = \json_decode($value, true);
                $mediaData->id       = $value['id'];
                $mediaData->ordering = $value['order'];

                if ($key === 0) {
                    $mediaData->is_featured = 1;
                } else {
                    $mediaData->is_featured = 0;
                }

                if (!empty($productId)) {
                    $imageModel->updateImageOrdering($mediaData);
                } else {
                    $imageModel->updateImageOrdering($mediaData, true);
                }
            }

            $response->status  = true;
            $response->message = Text::_('COM_EASYSTORE_APP_PRODUCT_IMAGE_ORDERING_SUCCESS');

            $this->sendResponse($response);
        }
    }

    /**
     * Gets image sizes for the given SVG file
     *
     * Uses the width/height attributes if present, or fallback to viewBox attribute
     *
     * @param string $filename File we want to retrieve information about.
     *
     * @return  \stdClass
     *
     * @since 1.0.4
     * @throws  \InvalidArgumentException
     * @throws  \RuntimeException
     */
    private function svgGetimagesize(string $filename)
    {
        $svgfile = simplexml_load_file(rawurlencode($filename));

        // Make sure the file exists.
        if (!$svgfile) {
            throw new \InvalidArgumentException('The image file does not exist.');
        }

        $width  = $this->formatSvgValue((string) $svgfile->attributes()->width);
        $height = $this->formatSvgValue((string) $svgfile->attributes()->height);

        if (!empty($width) && !empty($height)) {
            return (object) [
                'width'  => $width,
                'height' => $height,
            ];
        }

        $view_box = preg_split('/[\s,]+/', (string) $svgfile->attributes()->viewBox);

        if (empty($view_box)) {
            throw new \InvalidArgumentException('The svg file does not have view box.');
        }

        if (!empty($view_box[2]) && !empty($view_box[3])) {
            return (object) [
                'width'  => $view_box[2],
                'height' => $view_box[3],
            ];
        }
    }

    /**
     * Formats the SVG width/height value in case of unusual units
     *
     * @param string $value The value of the SVG width/height attribute.
     *
     * @return string
     *
     * @since 1.0.4
     */
    private function formatSvgValue(string $value): string
    {
        // No unit, we can use the value directly.
        if (is_numeric($value)) {
            return $value;
        }

        if (empty($value)) {
            return $value;
        }

        $px_pattern = '/([0-9]+)\s*px/i';

        // If pixel unit, remove the unit and return the numeric value.
        if (preg_match($px_pattern, $value)) {
            return preg_replace($px_pattern, '$1', $value);
        }

        // Return an empty string for other units.
        return '';
    }
}
