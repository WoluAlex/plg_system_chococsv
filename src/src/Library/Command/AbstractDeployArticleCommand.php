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
