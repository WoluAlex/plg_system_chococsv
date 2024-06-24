<?php

/**
 * @package        Joomla.UnitTest
 *
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 * @link           http://www.phpunit.de/manual/current/en/installation.html
 */

namespace Tests\Isolation;

use PHPUnit\Framework\TestCase;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\Session\Session;
use Joomla\Session\SessionInterface;

require_once __DIR__ . '/bootstrap.php';

/**
 * Base Unit Test case for common behaviour across unit tests
 *
 * @since   4.0.0
 */
abstract class IsolationTestCase extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        gc_collect_cycles();
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
