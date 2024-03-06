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
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Console\Command\AbstractCommand;
use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function basename;
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

    /**
     * Internal function to execute the command.
     *
     * @param InputInterface $input The input to inject into the command.
     * @param OutputInterface $output The output to inject into the command.
     *
     * @return  int  The command exit code
     *
     * @since   4.0.0
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $this->consoleOutputStyle = new SymfonyStyle($input, $output);

        try {
            $this->consoleOutputStyle->title('Chococsv: Deploy Joomla articles');

            $this->deploy();

            return Command::SUCCESS;
        } catch (LogicException $logicException) {
            $this->consoleOutputStyle->warning(
                sprintf(
                    '[%d] %s',
                    $logicException->getCode(),
                    $logicException->getMessage(),
                )
            );
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->consoleOutputStyle->error(
                sprintf(
                    '[%d] %s %s:%d',
                    $e->getCode(),
                    $e->getMessage(),
                    basename($e->getFile()),
                    $e->getLine()
                )
            );

            return Command::FAILURE;
        }

        return Command::FAILURE;
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
        $this->setDescription(
            'Deploy Joomla articles from CSV file to multiple destinations at once using Joomla Web Services'
        );
    }

    public function deploy()
    {
        /**
         * @var SiteApplication $siteApplication
         */
        $siteApplication = Factory::getContainer()
            ->get(SiteApplication::class);

        /**
         * @var MVCFactoryInterface $mvcFactory
         */
        $mvcFactory = $siteApplication
            ->bootComponent('chococsv')->getMVCFactory();

        $mvcFactory->createController(
            'Csv',
            'Site',
            ['base_path' => JPATH_ROOT . '/components/com_chococsv'],
            $siteApplication,
            $siteApplication->getInput()
        )
            ->execute('deploy');
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
