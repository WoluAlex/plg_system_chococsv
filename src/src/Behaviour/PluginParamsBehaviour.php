<?php

declare(strict_types=1);

/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later)
 */

namespace AlexApi\Plugin\Console\Chococsv\Behaviour;

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') || die;

/**
 * Plugin params Behaviour to get plugin params from Console Command
 */
trait PluginParamsBehaviour
{
    /**
     * Plugin type
     *
     * @var string $_type
     */
    protected string $_type = 'console';

    /**
     * Plugin name
     *
     * @var string $_name
     */
    protected string $_name = 'poet';

    /**
     * Get configured plugin params
     *
     * @return Registry
     */
    private function getParams(): Registry
    {
        return (new Registry(PluginHelper::getPlugin($this->_type, $this->_name)->params));
    }
}
