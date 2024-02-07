<?php

declare(strict_types=1);

/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later)
 */

namespace AlexApi\Plugin\Console\Chococsv\Console\Command;

defined('_JEXEC') || die;


/**
 * Anemic interface just to use polymorphism for other kinds of Content to generate
 */
interface DeployContentInterface
{
    public function deploy();
}
