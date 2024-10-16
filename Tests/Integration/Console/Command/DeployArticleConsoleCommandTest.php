<?php

/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later)
 */


namespace Tests\Integration\Console\Command;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use AlexApi\Plugin\System\Chococsv\Console\Command\DeployArticleConsoleCommand;
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Factory;
use Joomla\Console\Command\AbstractCommand;
use Joomla\DI\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Tests\Integration\ConsoleIntegrationTest;


final class DeployArticleConsoleCommandTest extends ConsoleIntegrationTest
{
    protected $app;
    private $deployArticleConsoleCommand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->getLanguage()->load('plg_system_chococsv');
        $this->deployArticleConsoleCommand = new DeployArticleConsoleCommand();
    }


    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->deployArticleConsoleCommand);
    }

    public function testThatApplicationInstanceIsConsoleApplication()
    {
        self::assertInstanceOf(ConsoleApplication::class, $this->app);
    }

    public function testThatContainerInstanceIsAvailable()
    {
        self::assertInstanceOf(Container::class, Factory::getContainer());
    }

    public function testThatDeployArticleCommandIsAbstractCommand()
    {
        $this->deployArticleConsoleCommand = new DeployArticleConsoleCommand();
        self::assertInstanceOf(AbstractCommand::class, $this->deployArticleConsoleCommand);
    }


    public function testDeploy()
    {
        $input = new ArgvInput([]);
        $output = new ConsoleOutput();
        $expected = Command::SUCCESS;
        $_ENV['NO_COLOR'] = 1;
        $actual = $this->deployArticleConsoleCommand->execute($input, $output);
        self::assertSame($expected, $actual);
    }
}
