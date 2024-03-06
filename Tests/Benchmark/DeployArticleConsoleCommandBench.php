<?php

declare(strict_types=1);
/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */

namespace Tests\Benchmark;

use AlexApi\Console\Routefinder\Command\WebServiceRoutesFindCommand;
use AlexApi\Plugin\Console\Chococsv\Console\Command\DeployArticleConsoleCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class DeployArticleConsoleCommandBench
{
    /**
     * #[Bench\Assert('mode(variant.time.avg) < 10ms')]
     */
    public function benchDeployArticleCommand()
    {
        try {
            $command = new DeployArticleConsoleCommand();

            $input = new ArgvInput();
            $output = new ConsoleOutput(OutputInterface::VERBOSITY_QUIET);

            $command->execute($input, $output);
        } catch (Throwable $e) {
            //NO-OP
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
