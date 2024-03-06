<?php

declare(strict_types=1);

/**
 *
 * @copyright (c) 2019 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later
 */


namespace Tests\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;

use Joomla\Database\DatabaseFactory;

use function array_merge;
use function defined;

defined('_JEXEC') || die;

/**
 * Class DatabaseHelper
 * @package Tests\Helper
 */
abstract class DatabaseHelper
{
    /**
     * @return mixed
     */
    final public static function createExternalInstance(array $currentOption = [])
    {
        $option = [];
        $option['driver'] = Factory::getApplication()->get('dbtype', 'mysqli');            // DatabaseHelper driver name
        $option['host'] = Factory::getApplication()->get(
            'host',
            $_SERVER['DB_HOST'] ?? ''
        );    // DatabaseHelper host name
        $option['user'] = Factory::getApplication()->get(
            'user',
            $_SERVER['DB_USER'] ?? ''
        );       // User for database authentication
        $option['password'] = Factory::getApplication()->get(
            'password',
            $_SERVER['DB_PASS'] ?? ''
        );   // Password for database authentication
        $option['database'] = Factory::getApplication()->get(
            'db',
            $_SERVER['DB_NAME'] ?? ''
        );     // DatabaseHelper name
        $option['prefix'] = Factory::getApplication()->get(
            'dbprefix',
            $_SERVER['DB_PREFIX'] ?? ''
        );           // DatabaseHelper prefix (may be empty)

        $option = array_merge($option, $currentOption);

        return (new DatabaseFactory())->getDriver($option['driver'], $option);
    }


    /**
     * Get previous primary key of provided table given the current primary key
     *
     *
     */
    final public static function getPreviousKey(
        string $table,
        string $primaryKeyColumn,
        int $currentPrimaryKeyValue
    ): ?int {
        $databaseDriver = Factory::getContainer()->get(DatabaseDriver::class);

        $query = $databaseDriver->getQuery(true);
        $query->select('MAX(a.' . $primaryKeyColumn . ') AS previous_key');
        $query->from($databaseDriver->qn($table, 'a'));
        $query->where($databaseDriver->qn('a.' . $primaryKeyColumn) . '<' . $currentPrimaryKeyValue);
        $databaseDriver->setQuery($query, 0, 1);

        $result = $databaseDriver->loadResult();

        return (($result === null) ? null : ((int)$result));
    }

    /**
     * Get previous primary key of provided table given the current primary key
     *
     *
     */
    final public static function getNextKey(string $table, string $primaryKeyColumn, int $currentPrimaryKeyValue): ?int
    {
        $databaseDriver = Factory::getContainer()->get(DatabaseDriver::class);

        $query = $databaseDriver->getQuery(true);
        $query->select('MIN(a.' . $primaryKeyColumn . ') AS next_key');
        $query->from($databaseDriver->qn($table, 'a'));
        $query->where($databaseDriver->qn('a.' . $primaryKeyColumn) . '>' . $currentPrimaryKeyValue);
        $databaseDriver->setQuery($query, 0, 1);

        $result = $databaseDriver->loadResult();

        return (($result === null) ? null : ((int)$result));
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
