<?php

declare(strict_types=1);

/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */

namespace AlexApi\Plugin\System\Chococsv\Library\Command;

// phpcs:disable PSR1.Files.SideEffects
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination\BasePath;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination\BaseUrl;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination\Destination;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination\TokenIndex;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination\TokenIndexMismatchException;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\State\DeployArticleCommandState;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Util\CsvUtil;
use Closure;
use Exception;
use InvalidArgumentException;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\Filesystem\Path;
use Joomla\Http\TransportInterface;
use Joomla\Registry\Registry;
use Joomla\Uri\Uri;
use League\Csv\Reader;
use RuntimeException;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use UnexpectedValueException;

use function array_combine;
use function basename;
use function bin2hex;
use function defined;
use function in_array;
use function is_dir;
use function is_file;
use function is_object;
use function json_decode;
use function json_encode;
use function random_bytes;
use function sprintf;
use function trim;

use const ANSI_COLOR_BLUE;
use const ANSI_COLOR_GREEN;
use const ANSI_COLOR_NORMAL;
use const ANSI_COLOR_RED;
use const CSV_START;
use const CUSTOM_LINE_END;
use const IS_CLI;
use const JSON_THROW_ON_ERROR;
use const PROJECT_TEST;

\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

trait DeployArticleCommandBehaviour
{


    protected array $retries = [];

    /**
     * @var TransportInterface|null
     */
    protected TransportInterface|null $transport = null;

    /**
     * @var StyleInterface|null
     */
    protected StyleInterface|null $consoleOutputStyle = null;

    public static function fromState(DeployArticleCommandState $deployArticleCommandState): static
    {
        return (new static($deployArticleCommandState));
    }

    /**
     * @return void
     */
    public function deploy(): void
    {
        // Show the ASCII Art banner or not
        $environmentAwareDisplay = (
        IS_CLI ?
            DeployArticleCommandState::ASCII_BANNER
            : sprintf(
            '<pre>%s</pre>',
            DeployArticleCommandState::ASCII_BANNER
        )
        );

        try {
            if ($this->deployArticleCommandState->shouldShowAsciiBanner()) {
                $this->enqueueMessage(
                    sprintf(
                        '%s %s %s%s',
                        ANSI_COLOR_BLUE,
                        $environmentAwareDisplay,
                        ANSI_COLOR_NORMAL,
                        CUSTOM_LINE_END
                    )
                );
            }

            $typedDestinationList = $this->computeDestinationsTypedArray($this->deployArticleCommandState);

            foreach ($typedDestinationList as $typedDestination) {
                try {
                    $this->csvReader(
                        $this->deployArticleCommandState,
                        $typedDestination
                    );
                } catch (TokenIndexMismatchException $tokenIndexMismatchException) {
                    // Log error and continue gracefully
                    $errorMessage = sprintf(
                        '%s[%d] %s %s:%d %s%s',
                        ANSI_COLOR_RED,
                        $tokenIndexMismatchException->getCode(),
                        $tokenIndexMismatchException->getMessage(),
                        basename($tokenIndexMismatchException->getFile()),
                        $tokenIndexMismatchException->getLine(),
                        ANSI_COLOR_NORMAL,
                        CUSTOM_LINE_END
                    );
                    if (in_array($this->deployArticleCommandState->getSaveReportToFile()?->asInt(), [1, 2], true)) {
                        Log::add($tokenIndexMismatchException, Log::ERROR, static::LOG_CATEGORY);
                    }
                    if ($this->deployArticleCommandState->getSilent()?->asInt() == 1) {
                        $this->enqueueMessage(
                            $errorMessage,
                            'error'
                        );
                    }
                    continue;
                }
            }
        } catch (Throwable $e) {
            $errorMessage = sprintf(
                '%s[%d] %s %s:%d %s%s',
                ANSI_COLOR_RED,
                $e->getCode(),
                $e->getMessage(),
                basename($e->getFile()),
                $e->getLine(),
                ANSI_COLOR_NORMAL,
                CUSTOM_LINE_END
            );
            if (in_array($this->deployArticleCommandState->getSaveReportToFile()?->asInt(), [1, 2], true)) {
                Log::add($e, Log::ERROR, static::LOG_CATEGORY);
            }
            if ($this->deployArticleCommandState->getSilent()?->asInt() == 1) {
                $this->enqueueMessage(
                    $errorMessage,
                    'error'
                );
            }
            // Rethrow exception to make the command fail as it should on failure
            throw $e;
        } finally {
            $this->deployArticleCommandState->withDone(true);
            $this->enqueueMessage(sprintf('Done%s', CUSTOM_LINE_END));
        }
    }

    /**
     * @return bool
     */
    protected function isSupported(): bool
    {
        return ComponentHelper::isInstalled('com_chococsv') && ComponentHelper::isEnabled('com_chococsv');
    }

    /**
     * @return array<Destination>
     */
    protected function computeDestinationsTypedArray(
        DeployArticleCommandState $givenDeployArticleState
    ): array {
        $rawDestinations = $givenDeployArticleState->getDestinations();
        if ($rawDestinations === []) {
            return [];
        }
        $output = [];

        $computedRawDestinations = (new Registry($rawDestinations))->toObject();

        foreach ($computedRawDestinations as $destination) {
            // Ignore when inactive
            if ($destination?->ref?->is_active == null) {
                continue;
            }

            if ($destination?->ref?->tokenindex == null) {
                continue;
            }

            $typedDestination = Destination::fromTokenIndex(
                TokenIndex::fromString($destination?->ref?->tokenindex ?? '')
            );

            // IMPORTANT!: Remember to set back the new state after using a "wither"
            $givenDeployArticleState = $givenDeployArticleState->withDone(false);
            // Public url of the sample csv used in this example (CHANGE WITH YOUR OWN CSV URL OR LOCAL CSV FILE)
            if ($destination?->ref?->is_local) {
                $localCsvFileFromParams = trim(((string)($destination?->ref?->local_file ?? '')));

                if (empty($localCsvFileFromParams)) {
                    throw new InvalidArgumentException('CSV local cannot be empty', 422);
                }

                $computedLocalCsvFileFromParams = '';
                if (defined('PROJECT_TEST') && is_dir((string)PROJECT_TEST)) {
                    $computedLocalCsvFileFromParams = Path::check(
                        sprintf('%s%s/com_chococsv/data/%s', (string)PROJECT_TEST, 'media', $localCsvFileFromParams),
                        PROJECT_TEST
                    );
                } elseif (defined('JPATH_ROOT') && is_dir((string)JPATH_ROOT)) {
                    $computedLocalCsvFileFromParams = Path::check(
                        sprintf('%s%s%s', (string)JPATH_ROOT, '/media/com_chococsv/data/', $localCsvFileFromParams),
                        JPATH_ROOT
                    );
                }
                if (is_file($computedLocalCsvFileFromParams)) {
                    $typedDestination = $typedDestination->withCsvUrl($computedLocalCsvFileFromParams);
                }
            } else {
                // For example: https://example.org/sample-data.csv';
                $remoteCsvFileFromParams = PunycodeHelper::urlToUTF8(
                    (string)($destination?->ref?->remote_file ?? '')
                );
                $typedDestination = $typedDestination->withCsvUrl($remoteCsvFileFromParams);
            }


// Line numbers we want in any order (e.g. 9,7-7,2-4,10,17-14,21). Leave empty '' to process all lines (beginning at line 2. Same as csv file)
            $whatLineNumbersYouWant = $destination?->ref?->what_line_numbers_you_want ?? '';

            $typedDestination = $typedDestination->withExpandedLineNumbers($whatLineNumbersYouWant);

            // Your Joomla! website base url
            $typedDestination = $typedDestination->withBaseUrl($destination?->ref?->base_url ?? '');

            // Your Joomla! Api Token (DO NOT STORE IT IN YOUR REPO USE A VAULT OR A PASSWORD MANAGER)
            $typedDestination = $typedDestination->withToken($destination?->ref?->auth_apikey ?? '');

            $typedDestination = $typedDestination->withBasePath(
                $destination?->ref?->base_path ?? '/api/index.php/v1'
            );

            // Other Joomla articles fields
            $typedDestination = $typedDestination->withExtraDefaultFieldKeys(
                $destination?->ref?->extra_default_fields ?? []
            );

// Add custom fields support (shout-out to Marc DECHÈVRE : CUSTOM KING)
// The keys are the columns in the csv with the custom fields names (that's how Joomla! Web Services Api work as of today)
// For the custom fields to work they need to be added in the csv and to exists in the Joomla! site.
            if ($destination?->ref?->toggle_custom_fields) {
                $givenCustomFields = $destination?->ref?->manually_custom_fields ?? []; // If not defined fallback to empty array
            } else {
                $givenCustomFields = $destination?->ref?->custom_fields ?? [];
            }
            $computedGivenCustomFields = (array)$givenCustomFields;
            $typedDestination = $typedDestination->withCustomFieldKeys($computedGivenCustomFields);
            $output[] = $typedDestination;
        }
        return $output;
    }


    /**
     * @return void
     * @throws Exception
     */
    protected function enqueueMessage(
        string $message,
        string $type = 'message'
    ): void {
        // Ignore empty messages
        if (empty($message)) {
            return;
        }

        // Messages at most 72 characters
        $message = HTMLHelper::_('string.truncate', $message, 72);

        $app = Factory::getApplication();
        if ($app instanceof ConsoleApplication) {
            $outputFormatter = new SymfonyStyle($app->getConsoleInput(), $app->getConsoleOutput());
            if ($type === 'message') {
                $type = 'success';
            }
            try {
                $outputFormatter->$type($message);
            } catch (Throwable) {
                $outputFormatter->text($message);
            }
        } elseif ($app instanceof CMSApplication) {
            $outputFormatter = [$app, 'enqueueMessage'];
            $outputFormatter($message, $type);
        }
    }


    protected function csvReader(
        DeployArticleCommandState $deployArticleCommandState,
        Destination $currentDestination
    ): void {
        $mergedKeys = CsvUtil::computeMergedKeys($currentDestination);

        // Assess robustness of the code by trying random key order
        //shuffle($mergedKeys);

        $computedCsvUrl = $currentDestination->getCsvUrl()?->asString();

        if ($computedCsvUrl == null) {
            throw new RuntimeException('Csv url cannot be empty. How useful could that be?', 0);
        }

        $linesYouWant = $currentDestination->getExpandedLineNumbers()?->asArray() ?? [];

        try {
            $records = Reader::createFromPath($computedCsvUrl)
                ->mapHeader($mergedKeys);

            if ($linesYouWant === []) {
                $dataCurrentCsvLine = CSV_START;
                $records->each(
                    function ($record) use (
                        &$dataCurrentCsvLine,
                        $mergedKeys,
                        $currentDestination,
                        $deployArticleCommandState
                    ) {
                        try {
                            $this->processEachCsvLineData(
                                $dataCurrentCsvLine++,
                                array_combine($mergedKeys, $record),
                                $currentDestination,
                                $deployArticleCommandState
                            );
                            return true;
                        } catch (TokenIndexMismatchException) {
                            return true;
                        } catch (Throwable $e) {
                            throw $e;
                        }
                    }
                );
            } else {
                foreach ($linesYouWant as $currentLineYouWant) {
                    try {
                        $this->processEachCsvLineData(
                            $currentLineYouWant,
                            array_combine($mergedKeys, $records->nth($currentLineYouWant)),
                            $currentDestination,
                            $deployArticleCommandState
                        );
                    } catch (TokenIndexMismatchException) {
                        continue;
                    } catch (Throwable $e) {
                        throw $e;
                    }
                }
            }
        } catch (Throwable $errorData) {
            if ($deployArticleCommandState->getSilent()?->asInt() === 1) {
                $this->enqueueMessage(
                    sprintf(
                        "%s%s,ERROR-L:%d%s%s",
                        ANSI_COLOR_RED,
                        $errorData->getMessage(),
                        $errorData->getLine(),
                        ANSI_COLOR_NORMAL,
                        CUSTOM_LINE_END
                    ),
                    'error'
                );
            }

            if (in_array($deployArticleCommandState->getSaveReportToFile()?->asInt(), [1, 2], true)) {
                Log::add($errorData, Log::ERROR, static::LOG_CATEGORY);
            }

            // Rethrow to bubble up for example to let console plugin catch this error
            throw $errorData;
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function processEachCsvLineData(
        int $dataCurrentCsvLine,
        array $givenData,
        Destination $currentDestination,
        DeployArticleCommandState|null $deployArticleCommandState = null
    ): void {
        try {
            if (!isset($this->retries[$dataCurrentCsvLine])) {
                $this->retries[$dataCurrentCsvLine] = 0;
            }

            if ($deployArticleCommandState === null) {
                $givenSilent = 1;
            } else {
                $givenSilent = $deployArticleCommandState->getSilent()->asInt();
            }

            // Handle CSV fields with nested JSON data
            $data = CsvUtil::nested($givenData, Closure::fromCallable([$this, 'enqueueMessage']), $givenSilent);

            $computedTokenIndex = TokenIndex::fromString($data['tokenindex'] ?? '');

            // If it's not matching token index stop here
            if (!$currentDestination->equals($computedTokenIndex)) {
                throw new TokenIndexMismatchException(
                    'CSV Line does not match configured destination tokenindex', 0
                );
            }

            // HTTP request headers
            $headers = [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/json',
                'X-Joomla-Token' => $currentDestination->getToken()?->asString() ?? '',
            ];

            // Article primary key. Usually 'id'
            $pk = (int)$data['id'];

            $currentResponse = static::processHttpRequest(
                $pk ? 'PATCH' : 'POST',
                static::endpoint(
                    $currentDestination->getBaseUrl(),
                    $currentDestination->getBasePath(),
                    $pk
                ),
                $data,
                $headers,
                DeployArticleCommandState::REQUEST_TIMEOUT
            );

            $decodedJsonOutput = json_decode(
                $currentResponse,
                false,
                512,
                JSON_THROW_ON_ERROR
            );

            if (!(isset($decodedJsonOutput) && is_object($decodedJsonOutput))) {
                return;
            }

            // don't show errors, handle them gracefully
            if (isset($decodedJsonOutput?->errors)) {
                // If article is potentially a duplicate (already exists with same alias)
                if (isset($decodedJsonOutput?->errors[0]->code) && $decodedJsonOutput?->errors[0]->code == 400) {
                    // Change the alias
                    $data['alias'] = sprintf('%s-%s', $data['alias'], bin2hex(random_bytes(4)));
                    // Retry
                    if ($this->retries[$dataCurrentCsvLine] < DeployArticleCommandState::MAX_RETRIES) {
                        ++$this->retries[$dataCurrentCsvLine];
                        $this->processEachCsvLineData($dataCurrentCsvLine, $data, $currentDestination);
                    } else {
                        throw new RuntimeException(
                            'Max retries reached. Could not process the request. Maybe a network issue .Stopping here',
                            0
                        );
                    }
                }
            } elseif (isset($decodedJsonOutput?->data->attributes)) {
                $successfulMessage =
                    sprintf(
                        "%s%s,L:%d,ID:%d,%s,%s,%s%s%s",
                        ANSI_COLOR_GREEN,
                        $data['tokenindex'],
                        $dataCurrentCsvLine,
                        $decodedJsonOutput->data->id,
                        $decodedJsonOutput->data->attributes->created,
                        HTMLHelper::_('string.abridge', $decodedJsonOutput->data->attributes->title, 30, 10),
                        $decodedJsonOutput->data->attributes->alias,
                        ANSI_COLOR_NORMAL,
                        CUSTOM_LINE_END
                    );
                if ($this->deployArticleCommandState->getSilent()?->asInt() == 1) {
                    $this->enqueueMessage($successfulMessage);
                }
                if (in_array($this->deployArticleCommandState->getSaveReportToFile()?->asInt(), [1, 2], true)) {
                    Log::add($successfulMessage, Log::DEBUG, static::LOG_CATEGORY);
                }
            }
        } catch (TokenIndexMismatchException $tokenMismatchException) {
            throw $tokenMismatchException; // Bubble up without notifying. It's not a failure
        } catch (Throwable $e) {
            $errorMessage = sprintf(
                "%s%s,ERROR-L:%d,L:%d%s%s",
                ANSI_COLOR_RED,
                $e->getMessage(),
                $e->getLine(),
                $dataCurrentCsvLine,
                ANSI_COLOR_NORMAL,
                CUSTOM_LINE_END
            );
            if ($this->deployArticleCommandState->getSilent()?->asInt() == 1) {
                $this->enqueueMessage($errorMessage, 'error');
            }
            if (in_array($this->deployArticleCommandState->getSaveReportToFile()?->asInt(), [1, 2], true)) {
                Log::add(
                    $errorMessage,
                    Log::ERROR,
                    static::LOG_CATEGORY
                );
            }
            throw new RuntimeException($errorMessage, 0, $e);
        }
    }

    /**
     * @return string
     */
    protected static function processHttpRequest(
        string $givenHttpVerb,
        string $endpoint,
        array|null $data,
        array $headers,
        int $timeout = 3
    ): string {
        try {
            $uri = (new Uri($endpoint));
            $response = static::getHttpClient()->request(
                $givenHttpVerb,
                $uri,
                ($data ? json_encode($data) : null),
                $headers,
                $timeout,
                static::USER_AGENT
            );

            if (empty($response)) {
                throw new UnexpectedValueException('Invalid response received after Http request. Cannot continue', 0);
            }

            return $response->body;
        } catch (Throwable) {
            throw new UnexpectedValueException('Invalid response received after Http request. Cannot continue', 0);
        }
    }

    /**
     * This time we need endpoint to be a function to make it more dynamic
     */
    protected static function endpoint(
        BaseUrl $givenBaseUrl,
        BasePath $givenBasePath,
        int|string|null $givenResourceId = null
    ): string {
        if (($givenBaseUrl?->asString() === '')
            && ($givenBasePath?->asString() === '')
        ) {
            throw new UnexpectedValueException('Endpoint cannot be empty', 422);
        }

        $initial = sprintf('%s%s/%s', $givenBaseUrl?->asString(), $givenBasePath?->asString(), 'content/articles');
        if (empty($givenResourceId)) {
            return $initial;
        }

        return sprintf('%s/%s', $initial, $givenResourceId);
    }

    public function testCsvReader(
        DeployArticleCommandState $deployArticleCommandState,
        Destination $currentDestination
    ): void {
        $this->csvReader(
            $deployArticleCommandState,
            $currentDestination
        );
    }

    public function testProcessEachCsvLineData(
        $dataCurrentCsvLine,
        $data,
        $currentDestination
    ): void {
        $this->processEachCsvLineData($dataCurrentCsvLine, $data, $currentDestination);
    }

    public static function testProcessHttpRequest(
        $givenHttpVerb,
        $endpoint,
        $data,
        $headers,
        $timeout
    ): string {
        return static::processHttpRequest($givenHttpVerb, $endpoint, $data, $headers, $timeout);
    }

    public static function testEndpoint(
        $givenBaseUrl,
        $givenBasePath,
        $givenResourceId
    ): string {
        return static::endpoint($givenBaseUrl, $givenBasePath, $givenResourceId);
    }

    public function testComputeDestinationsTypedArray(
        $rawDestinations
    ): array {
        return $this->computeDestinationsTypedArray($rawDestinations);
    }
}
