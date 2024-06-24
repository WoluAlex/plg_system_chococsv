<?php

/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */

namespace AlexApi\Plugin\System\Chococsv\Library\Command;

// phpcs:disable PSR1.Files.SideEffects

use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination\Destination;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\State\DeployArticleCommandState;

use function defined;

defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

interface TestableDeployContentInterface
{
    public function testComputeDestinationsTypedArray(DeployArticleCommandState $deployArticleCommandState);

    public function testCsvReader(
        DeployArticleCommandState $deployArticleCommandState,
        Destination $currentDestination
    );

    public function testProcessEachCsvLineData($dataCurrentCsvLine, $data, $currentDestination);

    public static function testProcessHttpRequest($givenHttpVerb, $endpoint, $data, $headers, $timeout);

    public static function testEndpoint($givenBaseUrl, $givenBasePath, $givenResourceId);

}
