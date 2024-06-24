<?php



/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       AGPL-3.0-or-later
 * @link          https://apiadept.com
 */

namespace Tests\Integration\Library\Chococsv;

use Joomla\Application\WebApplicationInterface;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\Session\Session;
use Joomla\Session\SessionInterface;
use Tests\Integration\IntegrationTestCase;

abstract class AdministratorIntegrationTestCase extends IntegrationTestCase
{
    protected WebApplicationInterface $app;

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

        $component = $app->bootComponent('chococsv');
        $this->app->set('dbo', $this->getTestDatabaseInstance());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        gc_collect_cycles();
    }


}
