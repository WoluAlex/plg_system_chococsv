<?php



/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later)
 */

namespace Tests\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseFactory;

use function array_merge;
use function defined;

defined('_JEXEC') || die;

/**
 * Class DatabaseHelper
 */
abstract class DatabaseHelper
{
    /**
     * @return mixed
     */
    final public static function createExternalInstance(array $currentOption = []): mixed
    {
        $option = [];
        $option['driver'] = Factory::getApplication()->get(
            'dbtype',
            'mysqli'
        );            // DatabaseHelper driver name
        $option['host'] = Factory::getApplication()->get('host', '');    // DatabaseHelper host name
        $option['user'] = Factory::getApplication()->get('user', '');       // User for database authentication
        $option['password'] = Factory::getApplication()->get('password', '');   // Password for database authentication
        $option['database'] = Factory::getApplication()->get('db', '');     // DatabaseHelper name
        $option['prefix'] = Factory::getApplication()->get(
            'dbprefix',
            ''
        );           // DatabaseHelper prefix (may be empty)

        $option = array_merge($option, $currentOption);

        return (new DatabaseFactory())->getDriver($option['driver'], $option);
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
