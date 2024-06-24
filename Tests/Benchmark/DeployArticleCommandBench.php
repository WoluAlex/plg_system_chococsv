<?php



/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later)
 */

namespace Tests\Benchmark;

use AlexApi\Plugin\System\Chococsv\Concrete\DeployArticleCommand;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\State\DeployArticleCommandState;

final class DeployArticleCommandBench
{
    /**
     * #[Bench\Assert('mode(variant.time.avg) < 10ms')]
     */
    public function benchDeployArticleCommand()
    {
        // Wether or not to show ASCII banner true to show , false otherwise. Default is to show the ASCII art banner
        $givenShowAsciiBanner = true;

// Silent mode
// 0: hide both response result and key value pairs
// 1: show response result only
// 2: show key value pairs only
// Set to 0 if you want to squeeze out performance of this script to the maximum
        $givenSilent = 1;


// Do you want a report after processing?
// 0: no report, 1: success & errors, 2: errors only
// When using report feature. Silent mode MUST be set to 1. Otherwise you might have unexpected results.
// Set to 0 if you want to squeeze out performance of this script to the maximum
// If enabled, this will create a output.json file
        $givenSaveReportToFile = 1;

        $givenDestinations = [
            [
                'ref' => [
                    'tokenindex' => 'app-001',
                    'base_url' => 'http://192.168.42.24:62780',
                    'base_path' => '/api/index.php/v1',
                    'show_form' => 1,
                    'is_active' => 1,
                    'auth_apikey' => 'YourJoomlaApiToken',
                    'is_local' => 1,
                    'remote_file' => '',
                    'local_file' => 'sample-data.csv',
                    'what_line_numbers_you_want' => '',
                    'extra_default_fields' => ['images', 'urls'],
                    'toggle_custom_fields' => 0,
                    'custom_fields' => [],
                    'manually_custom_fields' => [],
                ]
            ],
            [
                'ref' => [
                    'tokenindex' => 'app-002',
                    'show_form' => 0,
                    'is_active' => 0,
                ]
            ],
        ];

        $deployArticleCommandState = DeployArticleCommandState::fromState(
            $givenDestinations,
            $givenSilent,
            $givenSaveReportToFile
        );
        $deployArticleCommandState = $deployArticleCommandState->withAsciiBanner($givenShowAsciiBanner);
        $deployArticleCommand = DeployArticleCommand::fromState($deployArticleCommandState);
        $deployArticleCommand->deploy();
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
