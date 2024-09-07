<?php

declare(strict_types=1);

/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

defined('IS_CLI') || define('IS_CLI', PHP_SAPI == 'cli');
defined('CUSTOM_LINE_END') || define('CUSTOM_LINE_END', IS_CLI ? PHP_EOL : '<br>');
defined('ANSI_COLOR_RED') || define('ANSI_COLOR_RED', IS_CLI ? "\033[31m" : '');
defined('ANSI_COLOR_GREEN') || define('ANSI_COLOR_GREEN', IS_CLI ? "\033[32m" : '');
defined('ANSI_COLOR_BLUE') || define('ANSI_COLOR_BLUE', IS_CLI ? "\033[34m" : '');
defined('ANSI_COLOR_NORMAL') || define('ANSI_COLOR_NORMAL', IS_CLI ? "\033[0m" : '');

//Csv starts at line number : 2
defined('CSV_START') || define('CSV_START', 2);
