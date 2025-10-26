<?php

/**
 * @package     EasyStore.Plugin
 * @subpackage  System.easystorecore
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Plugin\System\EasyStoreCore\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Application\CMSApplication;
use JoomShaper\Component\EasyStore\Administrator\Traits\Migration;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

final class EasyStoreCore extends CMSPlugin implements SubscriberInterface
{
    use Migration;

    /**
     * function for getSubscribedEvents : new Joomla 4 feature
     *
     * @return array
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterRoute' => 'showMigrationPopup',
        ];
    }

    public function showMigrationPopup()
    {
        /** @var CMSApplication $app */
        $app     = Factory::getApplication();
        $input   = $app->getInput();
        $option  = $input->get('option');
        $view    = $input->get('view');
        $isAdmin = $app->isClient('administrator');

        if (!$isAdmin) {
            return false;
        }

        $doc = Factory::getDocument();
        $doc->addScriptOptions('easystore.base', rtrim(Uri::root(), '/'));
        $wa = $doc->getWebAssetManager();

        if ($option === 'com_easystore' && $view !== 'dashboard' && $view !== 'migration') {
            $wa->registerAndUseScript('plg_system_easystorecore.settingsteps.popup', 'plg_system_easystorecore/settingsteps.popup.js');
            $wa->registerAndUseStyle('plg_system_easystorecore.settingsteps.popup', 'plg_system_easystorecore/settingsteps.popup.css');

            return;
        }

        if (!$this->componentStatusCheck($this->setMigrateFrom())) {
            return false;
        }

        $wa->registerAndUseScript('plg_system_easystorecore.migration.popup', 'plg_system_easystorecore/migration.popup.js');
        $wa->registerAndUseStyle('plg_system_easystorecore.migration.popup', 'plg_system_easystorecore/migration.popup.css');
    }
}
