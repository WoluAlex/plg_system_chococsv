<?php

declare(strict_types=1);
/**
 * Chococsv
 *
 * @author     Mr Alexandre J-S William ELISÉ <code@apiadept.com>
 * @copyright  Copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ
 * @license    AGPL-3.0-or-later
 * @link       https://apiadept.com
 */

namespace AlexApi\Plugin\Console\Chococsv\Extension;

defined('_JEXEC') || die;

use AlexApi\Plugin\Console\Chococsv\Console\Command\DeployArticleConsoleCommand;
use Exception;
use Generator;
use Joomla\Application\ApplicationEvents;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Event\SubscriberInterface;

use function defined;

/**
 * Chococsv
 *
 * @since     0.1.0
 */
final class Chococsv extends CMSPlugin implements SubscriberInterface
{

    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var    bool $autoloadLanguage
     * @since  0.1.0
     */
    protected $autoloadLanguage = true;

    /**
     * @return array
     * @throws Exception
     * @since 0.1.0
     */
    public static function getSubscribedEvents(): array
    {
        // Subscribe to events only when in CLI Application
        if (Factory::getApplication()->isClient('cli')) {
            return [
                ApplicationEvents::BEFORE_EXECUTE => 'registerCLICommands',
            ];
        }

        return [];
    }

    public function __construct(&$subject, $config = [])
    {
        parent::__construct($subject, $config);

        // Make sure we are in CLI application before going further
        if (!Factory::getApplication()->isClient('cli')) {
            Factory::getApplication()->enqueueMessage(
                'This does not seem to be a CLI Application. Cannot continue.',
                'warning'
            );

            return;
        }

        $this->registerCLICommands();
    }

    /**
     * Register custom CLI Commands
     *
     * This part of the code is inspired by the work made by Clifford E Ford in official Joomla documentation about CLI
     * Commands example
     *
     * @see https://docs.joomla.org/J4.x:CLI_example_-_Onoffbydate by Clifford E Ford
     * @return void
     * @throws Exception
     */
    public function registerCLICommands(): void
    {
        $commands = $this->allowedCommands();
        foreach ($commands as $command) {
            if (!($command instanceof AbstractCommand)) {
                Factory::getApplication()->enqueueMessage(
                    'Command seems to have invalid type. Cannot continue.',
                    'warning'
                );

                continue;
            }

            // Everything seems ok. Add the Command
            Factory::getApplication()->addCommand($command);
        }
    }

    private function allowedCommands(): Generator
    {
        $deployArticleConsoleCommand = new DeployArticleConsoleCommand();
        $deployArticleConsoleCommand->setContainer(Factory::getContainer());
        yield $deployArticleConsoleCommand;
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
