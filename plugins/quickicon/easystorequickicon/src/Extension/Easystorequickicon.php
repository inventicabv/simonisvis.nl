<?php

/**
 * @package     EasyStore.Site
 * @subpackage  EasyStore.QuickIcon
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Plugin\Quickicon\Easystorequickicon\Extension;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Event\SubscriberInterface;
use Joomla\Module\Quickicon\Administrator\Event\QuickIconsEvent;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

final class Easystorequickicon extends CMSPlugin implements SubscriberInterface
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  3.1
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   4.3.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onGetIcons' => 'getEasyStoreQuickIcon',
        ];
    }

    /**
     * This method is called when the Quick Icons module is constructing its set
     * of icons. You can return an array which defines a single icon and it will
     * be rendered right after the stock Quick Icons.
     *
     * @param   QuickIconsEvent  $event  The event object
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function getEasyStoreQuickIcon(QuickIconsEvent $event): void
    {
        $context = $event->getContext();

        $user = $this->getApplication()->getIdentity();
		if ($context !== 'site_quickicon' || !$user->authorise('core.manage', 'com_easystore'))
		{
			return;
		}

        //@todo need to update the base64 code
        $style  = ".easystore-quick-icon {
            background-image: url(data:image/svg+xml,%3Csvg%20width%3D%2232%22%20height%3D%2232%22%20viewBox%3D%220%200%2032%2032%22%20fill%3D%22none%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%0A%3Cpath%20d%3D%22M23.3756%2017.4498H12.1863C11.2729%2017.4498%2010.5117%2016.6886%2010.5117%2015.7752C10.5117%2014.8618%2011.2729%2014.1006%2012.1863%2014.1006H23.3756C24.289%2014.1006%2025.0502%2014.8618%2025.0502%2015.7752C25.0502%2016.7647%2024.289%2017.4498%2023.3756%2017.4498Z%22%20fill%3D%22%23476285%22%2F%3E%0A%3Cpath%20d%3D%22M21.325%2029.5505C22.2078%2029.5505%2022.9235%2028.8348%2022.9235%2027.952C22.9235%2027.0692%2022.2078%2026.3535%2021.325%2026.3535C20.4422%2026.3535%2019.7266%2027.0692%2019.7266%2027.952C19.7266%2028.8348%2020.4422%2029.5505%2021.325%2029.5505Z%22%20fill%3D%22%23476285%22%2F%3E%0A%3Cpath%20d%3D%22M12.1102%2029.5505C12.993%2029.5505%2013.7087%2028.8348%2013.7087%2027.952C13.7087%2027.0692%2012.993%2026.3535%2012.1102%2026.3535C11.2274%2026.3535%2010.5117%2027.0692%2010.5117%2027.952C10.5117%2028.8348%2011.2274%2029.5505%2012.1102%2029.5505Z%22%20fill%3D%22%23476285%22%2F%3E%0A%3Cpath%20d%3D%22M25.7344%202.68258C24.8209%202.53034%2023.9837%203.13928%2023.8314%203.97657V4.05269L23.2986%206.86904H11.4243C7.23779%206.86904%203.8125%2010.8272%203.8125%2015.7748C3.8125%2020.7224%207.23779%2024.6806%2011.4243%2024.6806H22.1568C22.6897%2024.6806%2023.2225%2024.3761%2023.527%2023.9194C24.212%2022.8537%2023.4508%2021.4075%2022.1568%2021.4075H11.4243C8.68402%2021.4075%206.47661%2018.8956%206.47661%2015.7748C6.47661%2012.654%208.68402%2010.1421%2011.4243%2010.1421H24.6687C25.2015%2010.1421%2025.7344%209.83763%2026.0388%209.38092C26.1911%209.15257%2026.2672%208.8481%2026.2672%208.61974L26.3433%208.16304L26.9522%204.66163V4.58552C27.1806%203.74822%2026.5717%202.91093%2025.7344%202.68258Z%22%20fill%3D%22%23476285%22%2F%3E%0A%3C%2Fsvg%3E%0A);
            background-repeat: no-repeat;
            width: 2rem;
            height: 2rem;
        }";

        
        /** @var CMSApplication */
        $app = $this->getApplication();
        $app->getDocument()->getWebAssetManager()->addInlineStyle($style);

         // Add the icon to the result array
        $result = $event->getArgument('result', []);

        $result[] = [
            [
                'link'  => Route::_('index.php?option=com_easystore&view=dashboard'),
                'image' => 'easystore-quick-icon',
                'icon'  => '',
                'text'  => $this->getApplication()->getLanguage()->_('PLG_QUICKICON_EASYSTORE'),
                'id'    => 'plg_quickicon_easystorequickicon',
                'group' => 'MOD_QUICKICON_MAINTENANCE',
                'access' => ['core.manage', 'com_easystore']
            ],
        ];

        $event->setArgument('result', $result);
    }
    
}
