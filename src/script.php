<?php

declare(strict_types=1);

/**
 * @package    Chococsv
 *
 * @author     Mr Alexandre J-S William ELISÉ <code@apiadept.com>
 * @copyright  Copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ
 * @license    AGPL-3.0-or-later
 * @link       https://apiadept.com
 */

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

defined('_JEXEC') or die;

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            InstallerScriptInterface::class,
            new class ($container->get(AdministratorApplication::class))
                extends InstallerScript
                implements InstallerScriptInterface {
                /**
                 * Minimum PHP version to check
                 *
                 * @var    string
                 * @since  0.1.0
                 */
                protected $minimumPhp = '8.1.0';

                /**
                 * Minimum Joomla version to check
                 *
                 * @var    string
                 * @since  0.1.0
                 */
                protected $minimumJoomla = '5.0.0';

                private $app;

                protected $deleteFolders = [
                    '/plugins/console/chococsv/forms',
                    '/plugins/console/chococsv/language',
                    '/plugins/console/chococsv/services',
                    '/plugins/console/chococsv/src',
                ];

                public function __construct(AdministratorApplication $app)
                {
                    $this->app = $app;
                }

                public function preflight($type, $parent): bool
                {
                    $this->app->enqueueMessage(
                        sprintf(
                            '%s %s version: %s',
                            ucfirst($type),
                            $parent->getManifest()->name,
                            $parent->getManifest()->version
                        )
                    );

                    // Not called automatically
                    $this->removeFiles();

                    return true;
                }


                public function postflight($type, $parent): bool
                {
                    $this->app->enqueueMessage(
                        sprintf(
                            '%s %s version: %s',
                            ucfirst($type),
                            $parent->getManifest()->name,
                            $parent->getManifest()->version
                        )
                    );

                    return true;
                }

                public function install($parent): bool
                {
                    $this->app->enqueueMessage(
                        sprintf(
                            '%s %s version: %s',
                            'Install',
                            $parent->getManifest()->name,
                            $parent->getManifest()->version
                        )
                    );

                    return true;
                }

                public function update($parent): bool
                {
                    $this->app->enqueueMessage(
                        sprintf(
                            '%s %s version: %s',
                            'Update',
                            $parent->getManifest()->name,
                            $parent->getManifest()->version
                        )
                    );

                    return true;
                }

                public function uninstall($parent): bool
                {
                    $this->app->enqueueMessage(
                        sprintf(
                            '%s %s version: %s',
                            'Uninstall',
                            $parent->getManifest()->name,
                            $parent->getManifest()->version
                        )
                    );

                    return true;
                }
            }
        );
    }
};
