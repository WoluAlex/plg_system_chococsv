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

namespace AlexApi\Plugin\System\Chococsv\Extension;

defined('_JEXEC') || die;

use AlexApi\Plugin\System\Chococsv\Concrete\DeployArticleCommand;
use AlexApi\Plugin\System\Chococsv\Console\Command\DeployArticleConsoleCommand;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\State\DeployArticleCommandState;
use Exception;
use Generator;
use Joomla\Application\ApplicationEvents;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Event\Event;
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

        if (Factory::getApplication()->isClient('administrator')) {
            return [
                'onContentPrepareForm' => 'handleChococsvConfigForm'
            ];
        }

        if (Factory::getApplication()->isClient('site')) {
            return [
                'onAfterRoute' => 'onAfterRoute'
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

    public function handleChococsvConfigForm(Event $event): bool
    {
        [$form] = $event->getArguments();

        if (!$form || !($form instanceof Form)) {
            return false;
        }

        if ($form->getName() !== 'com_plugins.plugin') {
            return false;
        }

        $this->loadAssets();
        return true;
    }


    public function onAfterRoute()
    {
        $jinput = $this->getApplication()->input;

        // Intercepting calls to old Chococsv component implementation
        if (($jinput->getCmd('option') === 'plg_system_chococsv')
            && ($jinput->getCmd('task') === 'csv.deploy')
        ) {
            $this->deploy();
        }
    }


    private function loadAssets(): void
    {
        // Might be Console Application stop here
        if (!($this->getApplication() instanceof CMSApplication)) {
            return;
        }

        $document = $this->getApplication()->getDocument();

        // Not an Html Document. Hence cannot use Html related stuff. Stop here
        if (!($document instanceof HtmlDocument)) {
            return;
        }

        /**
         * @var WebAssetManager $wa
         */
        $wa = $document->getWebAssetManager();

        $wa->getRegistry()->addExtensionRegistryFile('plg_system_chococsv');
        $wa->usePreset('plg_system_chococsv.chococsv');
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
        // Add commands only if console mode is enabled
        if (!$this->params->get('enable_console', 1)) {
            return;
        }

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

    public function deploy(): void
    {
        // Wether or not to show ASCII banner true to show , false otherwise. Default is to show the ASCII art banner
        $givenShowAsciiBanner = (bool)$this->params->get('show_ascii_banner', 0);

// Silent mode
// 0: hide both response result and key value pairs
// 1: show response result only
// 2: show key value pairs only
// Set to 0 if you want to squeeze out performance of this script to the maximum
        $givenSilent = (int)$this->params->get('silent_mode', 1);


// Do you want a report after processing?
// 0: no report, 1: success & errors, 2: errors only
// When using report feature. Silent mode MUST be set to 1. Otherwise you might have unexpected results.
// Set to 0 if you want to squeeze out performance of this script to the maximum
// If enabled, this will create logs using native Joomla Logger
        $givenSaveReportToFile = (int)$this->params->get('save_report_to_file', 1);

        $givenDestinations = (array)$this->params->get('destinations', []);

        $deployArticleCommandState = DeployArticleCommandState::fromState(
            $givenDestinations,
            $givenSilent,
            $givenSaveReportToFile
        );
        $deployArticleCommandState->withAsciiBanner($givenShowAsciiBanner);
        $command = DeployArticleCommand::fromState($deployArticleCommandState);
        $command->deploy();
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
