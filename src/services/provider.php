<?php

declare(strict_types=1);

/**
 *
 * @author     Mr Alexandre J-S William ELISÉ <code@apiadept.com>
 * @copyright  Copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ
 * @license    AGPL-3.0-or-later
 * @link       https://apiadept.com
 */

use AlexApi\Plugin\System\Chococsv\Extension\Chococsv;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Filesystem\Path;

defined('_JEXEC') || die;


return new class implements ServiceProviderInterface {

    public function register(Container $container)
    {
        $givenDefines = dirname(__DIR__) . '/includes/defines.php';

        if (!file_exists($givenDefines))
        {
            Factory::getApplication()->enqueueMessage('Requirements not met. Stopping here.', 'warning');

            return;
        }

        // Load global constants
        require_once $givenDefines;

        $container->set(PluginInterface::class, function (Container $container) {

            $dispatcher = $container->get(DispatcherInterface::class);
            $plugin     = PluginHelper::getPlugin('system', 'chococsv');


            // Import the library loader if necessary.
            if (!class_exists('JLoader'))
            {
                require_once JPATH_PLATFORM . '/loader.php';

                // If JLoader still does not exist panic.
                if (!class_exists('JLoader'))
                {
                    throw new RuntimeException('Joomla Platform not loaded.');
                }
            }

            // Setup the autoloaders.
            JLoader::setup();

            JLoader::registerNamespace('League\\Csv\\', dirname(__DIR__) . '/vendor/league/csv/src');

            JLoader::registerNamespace('AlexApi\\Plugin\\System\\Chococsv\\', dirname(__DIR__) . '/src');

            $extension = (new Chococsv($dispatcher, (array) $plugin));
            $extension->setApplication(Factory::getApplication());

            return $extension;
        });
    }

    public function __debugInfo(): ?array
    {
        return null;
    }

    public function __serialize(): array
    {
        return [];
    }
};
