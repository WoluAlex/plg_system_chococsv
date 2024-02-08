<?php
declare(strict_types=1);

/**
 *
 * @author     Mr Alexandre J-S William ELISÉ <code@apiadept.com>
 * @copyright  Copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ
 * @license    AGPL-3.0-or-later
 * @link       https://apiadept.com
 */

use AlexApi\Plugin\Console\Chococsv\Extension\Chococsv;
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

defined('_JEXEC') || die;

return new class implements ServiceProviderInterface {

    public function register(Container $container)
    {
        $container->set(PluginInterface::class, function (Container $container) {
            $dispatcher = $container->get(DispatcherInterface::class);
            $plugin = PluginHelper::getPlugin('console', 'chococsv');

            $extension = new Chococsv($dispatcher, (array)$plugin);
            $extension->setApplication($container->get(ConsoleApplication::class));

            return $extension;
        });
    }
};
