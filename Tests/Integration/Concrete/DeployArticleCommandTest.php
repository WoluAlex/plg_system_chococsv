<?php


/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */

namespace Tests\Integration\Concrete;

use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination\BasePath;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination\BaseUrl;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination\Destination;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\State\DeployArticleCommandState;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Util\CsvUtil;
use InvalidArgumentException;
use Tests\Integration\IntegrationTestCase;
use Tests\Integration\SampleDeployArticleCommand;

use function array_intersect;
use function count;
use function fopen;
use function parse_ini_file;
use function print_r;

use const API_CONFIG_INI;
use const PROJECT_TEST;


final class DeployArticleCommandTest extends IntegrationTestCase
{
    private $deployArticleCommandState;

    private $deployArticleCommand;

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
                    'base_url' => self::$apiConfig['chococsv001']['BASE_URL'],
                    'base_path' => '/api/index.php/v1',
                    'show_form' => 1,
                    'is_active' => 1,
                    'auth_apikey' => self::$apiConfig['chococsv001']['JOOMLA_API_TOKEN'],
                    'is_local' => 1,
                    'remote_file' => '',
                    'local_file' => 'sample-data.csv',
                    'what_line_numbers_you_want' => '',
                    'extra_default_fields' => ['featured', 'images', 'urls'],
                    'toggle_custom_fields' => 0,
                    'custom_fields' => [],
                    'manually_custom_fields' => [],
                ]
            ],
            [
                'ref' => [
                    'tokenindex' => 'app-002',
                    'base_url' => self::$apiConfig['chococsv002']['BASE_URL'],
                    'base_path' => '/api/index.php/v1',
                    'show_form' => 1,
                    'is_active' => 1,
                    'auth_apikey' => self::$apiConfig['chococsv002']['JOOMLA_API_TOKEN'],
                    'is_local' => 1,
                    'remote_file' => '',
                    'local_file' => 'sample-data.csv',
                    'what_line_numbers_you_want' => '',
                    'extra_default_fields' => ['featured', 'images', 'urls'],
                    'toggle_custom_fields' => 0,
                    'custom_fields' => [],
                    'manually_custom_fields' => [],
                ]
            ],
        ];

        $this->deployArticleCommandState = DeployArticleCommandState::fromState(
            $givenDestinations,
            $givenSilent,
            $givenSaveReportToFile
        );
        $this->deployArticleCommandState = $this->deployArticleCommandState->withAsciiBanner($givenShowAsciiBanner);
        $this->deployArticleCommand = SampleDeployArticleCommand::fromState($this->deployArticleCommandState);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->deployArticleCommandState);
        unset($this->deployArticleCommand);
    }

    public function testThatDestinationMatchesIsInstanceOfDestination()
    {
        // Given
        $typedDestinations = $this->deployArticleCommand->testComputeDestinationsTypedArray(
            $this->deployArticleCommandState
        );

        // When
        $actual = $typedDestinations[0];

        // Then
        self::assertInstanceOf(Destination::class, $actual);
    }


    public function testThatDestinationMatchesCorrectTokenIndex()
    {
        // Given
        $typedDestinations = $this->deployArticleCommand->testComputeDestinationsTypedArray(
            $this->deployArticleCommandState
        );

        // When
        $actual = $typedDestinations[0];

        // Then
        self::assertSame('app-001', $actual->getTokenIndex()->asString());
    }

    public function testThatDestinationMatchesCorrectCsvUrl()
    {
        // Given
        $typedDestinations = $this->deployArticleCommand->testComputeDestinationsTypedArray(
            $this->deployArticleCommandState
        );

        // When
        $actual = $typedDestinations[0];

        // Then
        self::assertSame(PROJECT_TEST . 'media/plg_system_chococsv/data/sample-data.csv', $actual->getCsvUrl()->asString());
    }


    public function testTestEndpointWithEmptyValues()
    {
        $this->expectException(InvalidArgumentException::class);

        //Given
        $givenBaseUrl = BaseUrl::fromString('');
        $givenBasePath = BasePath::fromString('');
        $givenResourceId = '';

        //When
        $actual = SampleDeployArticleCommand::testEndpoint($givenBaseUrl, $givenBasePath, $givenResourceId);
    }

    public function testTestEndpointShouldBeValidWhenUsingHttpSchemeForBaseUrl()
    {
        //Given
        $givenBaseUrl = BaseUrl::fromString('http://example.org');
        $givenBasePath = BasePath::fromString('/api/index.php/v1');
        $givenResourceId = 0;

        //When
        $actual = SampleDeployArticleCommand::testEndpoint($givenBaseUrl, $givenBasePath, $givenResourceId);

        //Then
        $expected = 'http://example.org/api/index.php/v1/content/articles';

        self::assertSame($expected, $actual);
    }

    public function testTestEndpointShouldBeValidWhenUsingBasePathWithoutIndexPhp()
    {
        //Given
        $givenBaseUrl = BaseUrl::fromString('https://example.org');
        $givenBasePath = BasePath::fromString('/api/v1');
        $givenResourceId = 0;

        //When
        $actual = SampleDeployArticleCommand::testEndpoint($givenBaseUrl, $givenBasePath, $givenResourceId);

        //Then
        $expected = 'https://example.org/api/v1/content/articles';

        self::assertSame($expected, $actual);
    }


    public function testTestEndpointShouldNotAppendResourceIdWhenZero()
    {
        //Given
        $givenBaseUrl = BaseUrl::fromString('https://example.org');
        $givenBasePath = BasePath::fromString('/api/index.php/v1');
        $givenResourceId = 0;

        //When
        $actual = SampleDeployArticleCommand::testEndpoint($givenBaseUrl, $givenBasePath, $givenResourceId);

        //Then
        $expected = 'https://example.org/api/index.php/v1/content/articles';

        self::assertSame($expected, $actual);
    }

    public function testTestEndpointShouldNotAppendResourceIdWhenNull()
    {
        //Given
        $givenBaseUrl = BaseUrl::fromString('https://example.org');
        $givenBasePath = BasePath::fromString('/api/index.php/v1');
        $givenResourceId = null;

        //When
        $actual = SampleDeployArticleCommand::testEndpoint($givenBaseUrl, $givenBasePath, $givenResourceId);

        //Then
        $expected = 'https://example.org/api/index.php/v1/content/articles';

        self::assertSame($expected, $actual);
    }

    public function testTestEndpointShouldNotAppendResourceIdWhenEmptyString()
    {
        //Given
        $givenBaseUrl = BaseUrl::fromString('https://example.org');
        $givenBasePath = BasePath::fromString('/api/index.php/v1');
        $givenResourceId = '';

        //When
        $actual = SampleDeployArticleCommand::testEndpoint($givenBaseUrl, $givenBasePath, $givenResourceId);

        //Then
        $expected = 'https://example.org/api/index.php/v1/content/articles';

        self::assertSame($expected, $actual);
    }

    public function testTestEndpointShouldAppendResourceIdWhenPositiveInteger()
    {
        //Given
        $givenBaseUrl = BaseUrl::fromString('https://example.org');
        $givenBasePath = BasePath::fromString('/api/index.php/v1');
        $givenResourceId = 42;

        //When
        $actual = SampleDeployArticleCommand::testEndpoint($givenBaseUrl, $givenBasePath, $givenResourceId);

        //Then
        $expected = 'https://example.org/api/index.php/v1/content/articles/42';

        self::assertSame($expected, $actual);
    }

    public function testTestEndpointShouldAppendResourceIdWhenNonEmptyString()
    {
        //Given
        $givenBaseUrl = BaseUrl::fromString('https://example.org');
        $givenBasePath = BasePath::fromString('/api/index.php/v1');
        $givenResourceId = 'hello';

        //When
        $actual = SampleDeployArticleCommand::testEndpoint($givenBaseUrl, $givenBasePath, $givenResourceId);

        //Then
        $expected = 'https://example.org/api/index.php/v1/content/articles/hello';

        self::assertSame($expected, $actual);
    }


    public function testComputeCsvLinesWithAllLinesReturnsSameComputedParsedCSVHeaderColumnsCount()
    {
        // Given: sample csv file with 42 lines
        // And we want all the lines
        $resource = fopen(PROJECT_TEST . 'media/plg_system_chococsv/data/sample-data.csv', 'r');
        $orderedSet = CsvUtil::chooseLinesLikeAPrinter('');
        $typedDestinations = $this->deployArticleCommand->testComputeDestinationsTypedArray(
            $this->deployArticleCommandState
        );
        $currentTypedDestination = $typedDestinations[0];

        // When we call read csv file
        $expected = DeployArticleCommandState::DEFAULT_ARTICLE_KEYS + [13 => 'featured', 15 => 'images', 16 => 'urls'];
        CsvUtil::computeCsv(
            $resource,
            $orderedSet,
            CsvUtil::computeMergedKeys($currentTypedDestination),
            fn($successData) => self::assertCount(
                count(DeployArticleCommandState::DEFAULT_ARTICLE_KEYS) + 3,
                array_intersect($expected, $successData['csv_header']),
                'Current CSV line parsed data does not match computed CSV header columns (First Line in CSV)'
            ),
            fn($errorData) => print_r($errorData->getMessage(), true)
        );
    }

    public function testComputeCsvLinesWithAllLinesReturnsCorrectComputedParsedCSVHeaderColumns()
    {
        // Given: sample csv file with 42 lines
        // And we want all the lines
        $resource = fopen(PROJECT_TEST . 'media/plg_system_chococsv/data/sample-data.csv', 'r');
        $orderedSet = CsvUtil::chooseLinesLikeAPrinter('');
        $typedDestinations = $this->deployArticleCommand->testComputeDestinationsTypedArray(
            $this->deployArticleCommandState
        );
        $currentTypedDestination = $typedDestinations[0];

        // When we call read csv file
        $expected = DeployArticleCommandState::DEFAULT_ARTICLE_KEYS + [13 => 'featured', 15 => 'images', 16 => 'urls'];
        CsvUtil::computeCsv(
            $resource,
            $orderedSet,
            CsvUtil::computeMergedKeys($currentTypedDestination),
            fn($successData) => self::assertEqualsWithDelta(
                $expected,
                $successData['csv_header'],
                0.05,
                'Current CSV line parsed data does not match computed CSV header columns (First Line in CSV)'
            ),
            fn($errorData) => print_r($errorData->getMessage(), true)
        );
    }
}
