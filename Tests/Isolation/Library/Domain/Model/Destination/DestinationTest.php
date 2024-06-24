<?php

/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */

namespace Tests\Isolation\Library\Domain\Model\Destination;

use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination\Destination;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination\TokenIndex;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Isolation\IsolationTestCase;

final class DestinationTest extends IsolationTestCase
{
    public static function validTokenIndexDataProvider(): iterable
    {
        yield 'app-001' => ['app-001'];
        yield 'app-002' => ['app-002'];
        yield 'site-3' => ['site-3'];
        yield 'agency42' => ['agency42'];
        yield 'blog-11' => ['blog-11'];
        yield 't2' => ['t2'];
        yield 'Min length token index' => ['ab'];
        yield 'Max length Token index' => ['abcdefghijklmnopqrstuvwxyz0123456789ace'];
    }

    public static function invalidTokenIndexDataProvider(): iterable
    {
        yield 'app-00-1' => ['app-00-1'];
        yield 'app-00-2' => ['app-00-2'];
        yield '' => [''];
        yield '1' => ['1'];
        yield 'blog_11' => ['blog_11'];
        yield 't#2' => ['t#2'];
        yield 'Overflow token index' => ['abcdefghijklmnopqrstuvwxyz0123456789acid'];
    }


    #[DataProvider('validTokenIndexDataProvider')]
    public function testTokenIndexMustBeValid($validTokenIndex)
    {
        self::assertInstanceOf(TokenIndex::class, TokenIndex::fromString($validTokenIndex));
    }

    #[DataProvider('invalidTokenIndexDataProvider')]
    public function testInvalidTokenIndexShouldThrowInvalidArgumentException($invalidTokenIndex)
    {
        $this->expectException(InvalidArgumentException::class);
        TokenIndex::fromString($invalidTokenIndex);
    }

    public function testIsRemoteDestinationShouldReturnTrue()
    {
        $destination = Destination::fromTokenIndex(TokenIndex::fromString('app-001'));
        $destination = $destination->withBaseUrl('https://example.org');
        $destination = $destination->withBasePath('/api/v1');
        $destination = $destination->withToken('exampletoken');

        self::assertTrue($destination->isRemote());
    }

    public function testIsRemoteDestinationShouldReturnFalse()
    {
        $this->expectException(InvalidArgumentException::class);
        $destination = Destination::fromTokenIndex(TokenIndex::fromString('app-001'));
        $destination = $destination->withBaseUrl('');
        $destination = $destination->withBasePath('');
        $destination = $destination->withToken('');
    }
}
