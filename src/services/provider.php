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
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Filesystem\Path;

defined('_JEXEC') || die;


defined('IS_CLI') || define('IS_CLI', PHP_SAPI == 'cli');
defined('CUSTOM_LINE_END') || define('CUSTOM_LINE_END', IS_CLI ? PHP_EOL : '<br>');
defined('ANSI_COLOR_RED') || define('ANSI_COLOR_RED', IS_CLI ? "\033[31m" : '');
defined('ANSI_COLOR_GREEN') || define('ANSI_COLOR_GREEN', IS_CLI ? "\033[32m" : '');
defined('ANSI_COLOR_BLUE') || define('ANSI_COLOR_BLUE', IS_CLI ? "\033[34m" : '');
defined('ANSI_COLOR_NORMAL') || define('ANSI_COLOR_NORMAL', IS_CLI ? "\033[0m" : '');

//Csv starts at line number : 2
defined('CSV_START') || define('CSV_START', 2);


return new class implements ServiceProviderInterface {

    public function register(Container $container)
    {
        $autoloader = Path::check(JPATH_PLUGINS . '/system/chococsv/vendor/autoload.php', JPATH_PLUGINS);
        if (defined('PROJECT_TEST') && defined('PROJECT_ROOT')) {
            $autoloader = Path::check(
                PROJECT_ROOT . '/src/vendor/autoload.php',
                PROJECT_ROOT
            ); //useful ONLY when testing
        }
        if (!file_exists($autoloader)) {
            Factory::getApplication()->enqueueMessage(
                sprintf('File not found %s is required to continue. Stopping here.', $autoloader),
                'warning'
            );
            return;
        }

        require_once $autoloader;

        $container->set(PluginInterface::class, function (Container $container) {
            $dispatcher = $container->get(DispatcherInterface::class);
            $plugin = PluginHelper::getPlugin('system', 'chococsv');

            $extension = new Chococsv($dispatcher, (array)$plugin);
            $extension->setApplication($container->get(ConsoleApplication::class));

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
