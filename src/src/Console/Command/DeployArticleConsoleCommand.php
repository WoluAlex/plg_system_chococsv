<?php

declare(strict_types=1);
/**
 *                              .::::ANNIVERSARY EDITION::::.
 *
 * Add or Edit Joomla! Articles to multiple Joomla Sites Via API Using Streamed CSV
 * - When id = 0 in csv it's doing a POST. If alias exists it add a random slug at the end of your alias and do POST again
 * - When id > 0 in csv it's doing a PATCH. If alias exists it add a random slug at the end of your alias and do PATCH again
 * - Requires PHP 8.1 minimum. Now uses PHP Fibers.
 *
 * This is the last version of the script. Future development will shift focus on the new Joomla Console script.
 * Will develop future version using a Joomla Console Custom Plugin. Crafted specially for CLI-based interaction.
 *
 * @author        Mr Alexandre J-S William ELISÉ <code@apiadept.com>
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License version 3 (AGPLv3)
 * @link          https://apiadept.com
 */

namespace AlexApi\Plugin\Console\Chococsv\Console\Command;

use AlexApi\Component\Chococsv\Administrator\Command\DeployContentInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Console\Command\AbstractCommand;
use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function defined;
use function sprintf;

defined('_JEXEC') || die;

/**
 * Joomla Console Command to generate Article via Joomla Web Services
 *
 * @since 0.1.0
 */
final class DeployArticleConsoleCommand extends AbstractCommand implements ContainerAwareInterface,
                                                                           DeployContentInterface
{
    use ContainerAwareTrait;

    /**
     * The default command name
     *
     * @var    string
     * @since  4.0.0
     */
    protected static $defaultName = 'chococsv:deploy:articles';

    private SymfonyStyle|null $consoleOutputStyle = null;

    private Language|null $language = null;

    private function getComputedLanguage(): Language
    {
        $lang = $this->getApplication()->getLanguage() || Factory::getApplication()->getLanguage();
        $lang->load('plg_console_chococsv') || $lang->load('com_chococsv');

        return $lang;
    }

    /**
     * Internal function to execute the command.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  int  The command exit code
     *
     * @since   4.0.0
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->consoleOutputStyle = new SymfonyStyle($input, $output);

            $this->consoleOutputStyle->title(
                $this->language->translate('PLG_CONSOLE_CHOCOCSV_DEPLOY_ARTICLE_COMMAND_TITLE')
            );

            $this->deploy();
        } catch (Throwable $e) {
            $this->consoleOutputStyle->error(
                sprintf(
                    '%s%d%s%s',
                    $e->getMessage(),
                    $e->getLine(),
                    $e->getTraceAsString(),
                    $e->getPrevious() ? $e->getPrevious()->getTraceAsString() : ''
                )
            );

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    protected function configure(): void
    {
        try {
            $this->language = $this->getComputedLanguage();
            $this->setDescription(
                $this->language->translate('PLG_CONSOLE_CHOCOCSV_DEPLOY_ARTICLE_COMMAND_DESCRIPTION')
            );
        } catch (Throwable $e) {
            $this->consoleOutputStyle->error($e->getMessage());
        }
    }

    public function deploy()
    {
        /**
         * @var MVCFactoryInterface $mvcFactory
         */
        $mvcFactory = Factory::getContainer()
            ->get(SiteApplication::class)
            ->bootComponent('chococsv')->getMVCFactory();

        $mvcFactory->createController('Csv', 'Site')
            ->execute('deploy');
    }

}
