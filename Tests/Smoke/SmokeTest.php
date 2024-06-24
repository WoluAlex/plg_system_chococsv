<?php


/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */

namespace Tests\Smoke;

use Generator;
use Joomla\Http\HttpFactory;
use Joomla\Http\Response;
use Joomla\Http\Transport\Curl;
use Joomla\Http\Transport\Stream;
use Joomla\Http\TransportInterface;
use Joomla\Uri\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function parse_ini_file;
use function print_r;
use function sprintf;
use function str_replace;
use function trim;

use const API_CONFIG_INI;
use const PHP_EOL;

final class SmokeTest extends TestCase
{
    private TransportInterface $client;
    private static array $endpointCapabilities = [];

    private static array $apiConfig = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$apiConfig = parse_ini_file(API_CONFIG_INI, true);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::$apiConfig = [];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = (new HttpFactory())->getAvailableDriver(['Stream', 'Curl'],
            'Stream'
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $filtered = array_values(array_filter(self::$endpointCapabilities));
        if ($filtered !== []) {
            echo print_r(self::$endpointCapabilities, true) . PHP_EOL;
        }
        unset($this->client);
    }

    public function testStreamTransportIsSupported()
    {
        self::assertTrue(Stream::isSupported());
    }

    public function testCurlTransportIsSupported()
    {
        self::assertTrue(Curl::isSupported());
    }

    public function testUrlProviderIsValid()
    {
        $actual = self::getUrlMap();

        $expected = [
            'v1/content/articles' => ['v1/content/articles', ''],
            'v1/content/articles/:id' => ['v1/content/articles/:id', '']
        ];
        self::assertSame($expected, $actual);
    }

    #[DataProvider('urlProvider')]
    public function testPageEndpointCapabilities(string $path, string $query)
    {
        $baseUrl = self::$apiConfig['chococsv001']['BASE_URL'];
        $basePath = '/api/index.php';
        $uri = new Uri($baseUrl);

        $variables = [
            ':id' => 1,
        ];

        $pathModified = $this->replaceVariables($path, $variables);

        $uri->setPath(sprintf('%s/%s', $basePath, $pathModified));
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36';
        // Don't send payload to server
        $dataString = null;
        // HTTP request headers
        $headers = [];
        // Timeout in seconds
        $timeout = 3;
        $generator = $this->asyncRequest('OPTIONS', $uri, $dataString, $headers, $timeout, $userAgent);
        /**
         * @var Response $response
         */
        foreach ($generator as $response) {
            if ($response->getStatusCode() === 204) {
                self::$endpointCapabilities[$pathModified] = $response->getHeaders();
            }

            self::assertSame(
                204,
                $response->getStatusCode(),
                sprintf('Unexpected response %d for uri: %s', $response->getStatusCode(), $uri->toString())
            );
        }
    }

    #[DataProvider('urlProvider')]
    public function testPageIsNotFound(string $path, string $query)
    {
        $baseUrl = self::$apiConfig['chococsv001']['BASE_URL'];
        $basePath = '/api/index.php';
        $uri = new Uri($baseUrl);

        $path = 'notfound';

        $variables = [
            ':id' => 1,
        ];

        $pathModified = $this->replaceVariables($path, $variables);
        $queryModified = $this->replaceVariables($query, $variables);

        $uri->setPath(sprintf('%s/%s', $basePath, $pathModified));
        $uri->setQuery($queryModified);
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36';
        // Don't send payload to server
        $dataString = null;
        // HTTP request headers
        $headers = [
            'Accept' => 'application/vnd.api+json',
            'X-Joomla-Token' => trim((string) self::$apiConfig['chococsv001']['JOOMLA_API_TOKEN']),
        ];
        // Timeout in seconds
        $timeout = 3;
        $generator = $this->asyncRequest('GET', $uri, $dataString, $headers, $timeout, $userAgent);
        foreach ($generator as $response) {
            self::assertSame(
                404,
                $response->getStatusCode(),
                sprintf('Unexpected response %d for uri: %s', $response->getStatusCode(), $uri->toString())
            );
        }
    }


    public static function urlProvider(): array
    {
        return self::getUrlMap();
    }

    private function asyncRequest($verb, $uri, $dataString, $headers, $timeout, $userAgent): Generator
    {
        yield $this->client->request(strtoupper((string) $verb), $uri, $dataString, $headers, $timeout, $userAgent);
    }

    private static function getUrlMap(): array
    {
        return [
            'v1/content/articles' => ['v1/content/articles', ''],
            'v1/content/articles/:id' => ['v1/content/articles/:id', '']
        ];
    }

    private function replaceVariables(string $subject, array $variables): array|string
    {
        return str_replace(array_keys($variables), array_values($variables), $subject);
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
