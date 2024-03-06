<?php

declare(strict_types=1);

namespace Tests\Integration;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Version;
use Joomla\Session\SessionInterface;
use PHPUnit\Framework\TestCase;

use Tests\Helper\DatabaseHelper;

use function define;
use function defined;

// Define the Joomla version if not already defined.
defined('JVERSION') or define('JVERSION', (new Version())->getShortVersion());


class IntegrationTest extends TestCase
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
        $container->alias('session', 'session.cli')
            ->alias('JSession', 'session.cli')
            ->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
            ->alias(Session::class, 'session.cli')
            ->alias(SessionInterface::class, 'session.cli');

// Instantiate the application.
        $app = $container->get(ConsoleApplication::class);
        $app->setVersion(JVERSION);

// Set the application as global app
        Factory::$application = $app;

        $this->app = $app;
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
