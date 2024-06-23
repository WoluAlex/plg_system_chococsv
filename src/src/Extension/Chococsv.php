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

use AlexApi\Plugin\System\Chococsv\Console\Command\DeployArticleConsoleCommand;
use Exception;
use Generator;
use Joomla\Application\ApplicationEvents;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\LibraryHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

use function defined;
use function file_exists;

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

        if ($form->getName() !== 'com_config.component') {
            return false;
        }

        $currentComponent = Factory::getApplication()->getInput()->get('component');

        if ($currentComponent !== 'com_chococsv') {
            return false;
        }

        $this->loadAssets();
        return true;
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

        $wa->getRegistry()->addExtensionRegistryFile($this->option ?? 'com_chococsv');
        $wa->usePreset('com_chococsv.chococsv');
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

        if (!LibraryHelper::isEnabled('lib_chococsv')) {
            Factory::getApplication()->enqueueMessage(
                Text::_('PLG_SYSTEM_CHOCOCSV_LIBRARY_REQUIRED_DEPENDENCY_NOT_ENABLED'),
                'warning'
            );
            return;
        }

        if (!file_exists(JPATH_LIBRARIES . '/lib_chococsv/library.php')) {
            Factory::getApplication()->enqueueMessage(
                Text::_('PLG_SYSTEM_CHOCOCSV_LIBRARY_REQUIRED_DEPENDENCY_NOT_FOUND'),
                'warning'
            );
            return;
        }

        require_once JPATH_LIBRARIES . '/lib_chococsv/library.php';

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
