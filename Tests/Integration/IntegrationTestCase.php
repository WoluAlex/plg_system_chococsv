<?php


/**
 * @package        Joomla.UnitTest
 *
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 * @link           http://www.phpunit.de/manual/current/en/installation.html
 */

namespace Tests\Integration;

use AlexApi\Plugin\System\Chococsv\Extension\Chococsv;
use Joomla\Application\WebApplicationInterface;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Session\Session;
use Joomla\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Tests\Helper\DatabaseHelper;

/**
 * Base Integration Test case for common behaviour across unit tests
 *
 * @since   4.0.0
 */
abstract class IntegrationTestCase extends TestCase
{
    protected WebApplicationInterface $app;

    protected Chococsv $plugin;


    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('mysqli')) {
            static::markTestSkipped(
                'The MySQLi extension is not available',
            );
        }

        // Boot the DI container
        $container = Factory::getContainer();

        /*
         * Alias the session service keys to the web session service as that is the primary session backend for this application
         *
         * In addition to aliasing "common" service keys, we also create aliases for the PHP classes to ensure autowiring objects
         * is supported.  This includes aliases for aliased class names, and the keys for aliased class names should be considered
         * deprecated to be removed when the class name alias is removed as well.
         */
        $container->alias('session.web', 'session.web.administrator')
            ->alias('session', 'session.web.administrator')
            ->alias('JSession', 'session.web.administrator')
            ->alias(\Joomla\CMS\Session\Session::class, 'session.web.administrator')
            ->alias(Session::class, 'session.web.administrator')
            ->alias(SessionInterface::class, 'session.web.administrator');

// Instantiate the application.
        $app = $container->get(AdministratorApplication::class);

// Set the application as global app
        Factory::$application = $app;

        $this->app = $app;

        $provider = require PROJECT_ROOT . '/src/services/provider.php';

        $container->registerServiceProvider($provider);

        $this->plugin = $this->app->bootPlugin('chococsv', 'system');
    }


    protected function tearDown(): void
    {
        parent::tearDown();
        gc_collect_cycles();
    }


    protected function getTestDatabaseInstance(): DatabaseInterface
    {
        $testOptions = ['database' => 'bdd_chococsv'];

        return DatabaseHelper::createExternalInstance($testOptions);
    }

    public function __debugInfo(): ?array
    {
        return null;
    }

    public function __serialize(): array
    {
        return [];
    }

}
