<?php

declare(strict_types=1);

/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */

namespace AlexApi\Plugin\System\Chococsv\Library\Command;

use AlexApi\Plugin\System\Chococsv\Library\Behaviour\WebserviceToolboxBehaviour;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\State\DeployArticleCommandState;
use RuntimeException;

use function define;
use function defined;
use function ini_set;

use const E_ALL;
use const E_DEPRECATED;
use const IS_CLI;
use const PHP_EOL;
use const PHP_SAPI;

\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

ini_set('error_reporting', E_ALL & ~E_DEPRECATED);
ini_set('error_log', '');
ini_set('log_errors', 1);
ini_set('log_errors_max_len', 4096);
ini_set('auto_detect_line_endings', 1);

defined('IS_CLI') || define('IS_CLI', PHP_SAPI == 'cli');
defined('CUSTOM_LINE_END') || define('CUSTOM_LINE_END', IS_CLI ? PHP_EOL : '<br>');
defined('ANSI_COLOR_RED') || define('ANSI_COLOR_RED', IS_CLI ? "\033[31m" : '');
defined('ANSI_COLOR_GREEN') || define('ANSI_COLOR_GREEN', IS_CLI ? "\033[32m" : '');
defined('ANSI_COLOR_BLUE') || define('ANSI_COLOR_BLUE', IS_CLI ? "\033[34m" : '');
defined('ANSI_COLOR_NORMAL') || define('ANSI_COLOR_NORMAL', IS_CLI ? "\033[0m" : '');

defined('CSV_SEPARATOR') || define('CSV_SEPARATOR', "\x2C");
defined('CSV_ENCLOSURE') || define('CSV_ENCLOSURE', "\x22");
defined('CSV_ESCAPE') || define('CSV_ESCAPE', "\x22");
defined('CSV_ENDING') || define('CSV_ENDING', "\x0D\x0A");

//Csv starts at line number : 2
defined('CSV_START') || define('CSV_START', 2);

/**
 *
 */
abstract class AbstractDeployArticleCommand implements DeployContentInterface, TestableDeployContentInterface
{
    use WebserviceToolboxBehaviour;
    use DeployArticleCommandBehaviour;

    protected const LOG_CATEGORY = 'lib_chococsv.deploy.article.command';

    protected const USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36';


    protected function __construct(protected DeployArticleCommandState $deployArticleCommandState)
    {
        if (!$this->isSupported()) {
            throw new RuntimeException('This feature is not supported on your platform.', 501);
        }
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
