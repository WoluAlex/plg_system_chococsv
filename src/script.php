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
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseDriver;
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
                protected $minimumJoomla = '4.0.0';

                protected $deleteFolders = [
                    '/plugins/system/chococsv/forms',
                    '/plugins/system/chococsv/language',
                    '/plugins/system/chococsv/services',
                    '/plugins/system/chococsv/src',
                ];

                public function __construct(private readonly AdministratorApplication $app)
                {
                }

                public function preflight($type, $parent): bool
                {
                    $outcome = parent::preflight($type, $parent);
                    if (!$outcome) {
                        return false;
                    }

                    $this->app->enqueueMessage(
                        sprintf(
                            '%s %s version: %s',
                            ucfirst((string)$type),
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
                            ucfirst((string)$type),
                            $parent->getManifest()->name,
                            $parent->getManifest()->version
                        )
                    );

                    $sql = <<<SQL
UPDATE #__extensions AS e SET e.enabled = 1, e.state = 1 WHERE e.type = 'plugin' AND e.element = 'chococsv' AND e.folder = 'system'
SQL;
                    return $this->runQuery($sql);
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

                private function runQuery($query)
                {
                    try {
                        $db = Factory::getContainer()->get(DatabaseDriver::class);
                        $db->setQuery($query);
                        return $db->execute();
                    } catch (Throwable $e) {
                        $this->app->enqueueMessage($e->getMessage(), 'warning');
                    }
                    return false;
                }
            }
        );
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
