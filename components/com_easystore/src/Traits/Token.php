<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Traits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;

/**
 * Token trait
 */
trait Token
{
    /**
     * Set token value into cookie
     *
     * @param string $token Token value
     * @return void
     * @since 1.0.0
     */
    public function setToken(string $token)
    {
        $options = [
            'expires'  => time() + 3600 * 30, // expires in 30 days, will be updated later.
            'httpOnly' => true,
            'path'     => '/',
        ];

        Factory::getApplication()->getInput()->cookie->set('com_easystore_cart', $token, $options);
    }

    /**
     * Create new cookie value
     *
     * @return mixed
     * @since 1.0.0
     */
    public function createToken()
    {
        $token = EasyStoreHelper::generateUuidV4();
        $this->setToken($token);

        return $token;
    }

    /**
     * Get Token value
     *
     * @return mixed
     * @since 1.0.0
     */
    public function getToken()
    {
        return Factory::getApplication()->getInput()->cookie->get('com_easystore_cart');
    }

    /**
     * Check token value
     *
     * @return bool
     * @since 1.0.0
     */
    public function hasToken()
    {
        return !is_null(Factory::getApplication()->getInput()->cookie->get('com_easystore_cart'));
    }

    /**
     * Remove token from cookie
     *
     * @return void
     * @since 1.0.0
     */
    public function removeToken()
    {
        if ($this->hasToken()) {
            $options = [
                'expires'  => time() - 3600,
                'path'     => '/',
                'httpOnly' => true,
            ];
            Factory::getApplication()->getInput()->cookie->set('com_easystore_cart', '', $options);
        }
    }
}
