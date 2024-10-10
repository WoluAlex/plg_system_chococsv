<?php

/**
 * @package        Joomla.UnitTest
 *
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 * @link           http://www.phpunit.de/manual/current/en/installation.html
 */

namespace Tests\Isolation;

use PHPUnit\Framework\TestCase;


/**
 * Base Unit Test case for common behaviour across unit tests
 *
 * @since   4.0.0
 */
abstract class IsolationTestCase extends TestCase
{

    protected function tearDown(): void
    {
        parent::tearDown();
        gc_collect_cycles();
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
