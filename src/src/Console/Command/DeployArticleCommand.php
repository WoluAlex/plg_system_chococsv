<?php

declare(strict_types=1);
/**
 *                              .::::ANNIVERSARY EDITION::::.
 *
 * Add or Edit Joomla! Articles to multiple Joomla Sites Via API Using Streamed CSV
 * - When id = 0 in csv it's doing a POST. If alias exists it add a random slug at the end of your alias and do POST again
 * - When id > 0 in csv it's doing a PATCH. If alias exists it add a random slug at the end of your alias and do PATCH again
 * - Requires PHP 8.1 minimum. Now uses PHP Fibers.
 *
 * This is the last version of the script. Future development will shift focus on the new Joomla Console script.
 * Will develop future version using a Joomla Console Custom Plugin. Crafted specially for CLI-based interaction.
 *
 * @author        Mr Alexandre J-S William ELISÉ <code@apiadept.com>
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License version 3 (AGPLv3)
 * @link          https://apiadept.com
 */

namespace AlexApi\Plugin\Console\Chococsv\Console\Command;

use AlexApi\Plugin\Console\Chococsv\Behaviour\PluginParamsBehaviour;
use AlexApi\Plugin\Console\Chococsv\Behaviour\WebserviceToolboxBehaviour;
use DomainException;
use Joomla\CMS\Language\Text;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Console\Command\AbstractCommand;
use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use Joomla\Http\TransportInterface;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function array_combine;
use function array_intersect;
use function array_intersect_key;
use function array_merge;
use function array_unique;
use function count;
use function define;
use function defined;
use function explode;
use function fclose;
use function feof;
use function file_exists;
use function fopen;
use function in_array;
use function ini_set;
use function is_readable;
use function is_resource;
use function is_string;
use function json_decode;
use function json_encode;
use function max;
use function min;
use function range;
use function sort;
use function sprintf;
use function str_contains;
use function str_getcsv;
use function str_replace;
use function stream_get_line;
use function stream_set_blocking;
use function strlen;
use function strpos;
use function trim;

use const ANSI_COLOR_BLUE;
use const ANSI_COLOR_GREEN;
use const ANSI_COLOR_NORMAL;
use const ANSI_COLOR_RED;
use const CSV_ENCLOSURE;
use const CSV_ESCAPE;
use const CSV_PROCESSING_REPORT_FILEPATH;
use const CSV_SEPARATOR;
use const CSV_START;
use const CUSTOM_LINE_END;
use const E_ALL;
use const E_DEPRECATED;
use const IS_CLI;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;
use const PHP_SAPI;
use const SORT_ASC;
use const SORT_NATURAL;

defined('_JEXEC') || die;

/**
 * Joomla Console Command to generate Article via Joomla Web Services
 *
 * @since 0.1.0
 */
final class DeployArticleCommand extends AbstractCommand implements ContainerAwareInterface, DeployContentInterface
{
    use ContainerAwareTrait;
    use WebserviceToolboxBehaviour;
    use PluginParamsBehaviour;

    /**
     * @var InputInterface|null $input
     */
    private ?InputInterface $input = null;

    /**
     * The default command name
     *
     * @var    string
     * @since  4.0.0
     */
    protected static $defaultName = 'chococsv:deploy:articles';

    private const ASCII_BANNER = <<<TEXT
    __  __     ____         _____                              __                      __
   / / / ___  / / ____     / ___/__  ______  ___  _____       / ____  ____  ____ ___  / ___  __________
  / /_/ / _ \/ / / __ \    \__ \/ / / / __ \/ _ \/ ___/  __  / / __ \/ __ \/ __ `__ \/ / _ \/ ___/ ___/
 / __  /  __/ / / /_/ /   ___/ / /_/ / /_/ /  __/ /     / /_/ / /_/ / /_/ / / / / / / /  __/ /  (__  )
/_/ /_/\___/_/_/\____/   /____/\__,_/ .___/\___/_/      \____/\____/\____/_/ /_/ /_/_/\___/_/  /____/
                                   /_/
TEXT;

    private const REQUEST_TIMEOUT = 3;

    private TransportInterface|null $transport;

    private StyleInterface|null $consoleOutputStyle;

    private bool $showAsciiBanner = false;
    private int $silent = 0;
    private string $csvUrl = '';
    private string $whatLineNumbersYouWant = '';
    private array $extraDefaultFieldKeys = [];

    private array $customFieldKeys = [];
    private array $failedCsvLines = [];
    private array $successfulCsvLines = [];

    private bool $isDone = false;
    private bool $isExpanded = false;
    private int $saveReportToFile = 0;

    private array $token = [];

    private array $baseUrl = [];

    private array $basePath = [];


    /**
     * Internal function to execute the command.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  int  The command exit code
     *
     * @since   4.0.0
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;

        $this->consoleOutputStyle = new SymfonyStyle($input, $output);

        $this->consoleOutputStyle->title(Text::_('PLG_CONSOLE_CHOCOCSV_DEPLOY_ARTICLE_COMMAND_TITLE'));

        ini_set('auto_detect_line_endings', true);

        ini_set('error_reporting', E_ALL & ~E_DEPRECATED);
        ini_set('error_log', '');
        ini_set('log_errors', 1);
        ini_set('log_errors_max_len', 4096);

        defined('IS_CLI') || define('IS_CLI', PHP_SAPI == 'cli');
        defined('CUSTOM_LINE_END') || define('CUSTOM_LINE_END', IS_CLI ? PHP_EOL : '<br>');
        defined('ANSI_COLOR_RED') || define('ANSI_COLOR_RED', IS_CLI ? "\033[31m" : '');
        defined('ANSI_COLOR_GREEN') || define('ANSI_COLOR_GREEN', IS_CLI ? "\033[32m" : '');
        defined('ANSI_COLOR_BLUE') || define('ANSI_COLOR_BLUE', IS_CLI ? "\033[34m" : '');
        defined('ANSI_COLOR_NORMAL') || define('ANSI_COLOR_NORMAL', IS_CLI ? "\033[0m" : '');

        defined('CSV_SEPARATOR') || define('CSV_SEPARATOR', "\x2C");
        defined('CSV_ENCLOSURE') || define('CSV_ENCLOSURE', "\x22");
        defined('CSV_ESCAPE') || define('CSV_ESCAPE', "\x22");
        defined('CSV_ENDING') || define('CSV_ENDING', "\x0D\x0A");

//Csv starts at line number : 2
        defined('CSV_START') || define('CSV_START', 2);
// This MUST be a json file otherwise it might fail
        defined('CSV_PROCESSING_REPORT_FILEPATH') || define(
            'CSV_PROCESSING_REPORT_FILEPATH',
            Path::clean(JPATH_PUBLIC . '/media/plg_console_chococsv/report/output.json')
        );


// Wether or not to show ASCII banner true to show , false otherwise. Default is to show the ASCII art banner
        $this->showAsciiBanner = (bool)$this->getParams()->get('show_ascii_banner', 0);

// Public url of the sample csv used in this example (CHANGE WITH YOUR OWN CSV URL OR LOCAL CSV FILE)
        $isLocal = (bool)$this->getParams()->get('is_local', 1);

// IF THIS URL DOES NOT EXIST IT WILL CRASH THE SCRIPT. CHANGE THIS TO YOUR OWN URL
        // For example: https://example.org/sample-data.csv';
        $this->csvUrl = PunycodeHelper::urlToUTF8((string)$this->getParams()->get('remote_file', ''));
        if ($isLocal) {
            $localCsvFile = Path::clean($this->getParams()->get('local_file', ''));
            if (is_readable($localCsvFile)) {
                $this->csvUrl = $localCsvFile;
            }
        }

// Silent mode
// 0: hide both response result and key value pairs
// 1: show response result only
// 2: show key value pairs only
// Set to 0 if you want to squeeze out performance of this script to the maximum
        $this->silent = (int)$this->getParams()->get('silent_mode', 0);

// Line numbers we want in any order (e.g. 9,7-7,2-4,10,17-14,21). Leave empty '' to process all lines (beginning at line 2. Same as csv file)
        $this->whatLineNumbersYouWant = $this->getParams()->get('what_line_numbers_you_want', '');

// Do you want a report after processing?
// 0: no report, 1: success & errors, 2: errors only
// When using report feature. Silent mode MUST be set to 1. Otherwise you might have unexpected results.
// Set to 0 if you want to squeeze out performance of this script to the maximum
// If enabled, this will create a output.json file
        $this->saveReportToFile = (int)$this->getParams()->get('save_report_to_file', 0);

// Show the ASCII Art banner or not
        $enviromentAwareDisplay = (IS_CLI ? self::ASCII_BANNER : sprintf('<pre>%s</pre>', self::ASCII_BANNER));

        $this->failedCsvLines     = [];
        $this->successfulCsvLines = [];
        $this->isDone             = false;

        $this->enqueueMessage(
            $this->showAsciiBanner ? sprintf(
                '%s %s %s%s',
                ANSI_COLOR_BLUE,
                $enviromentAwareDisplay,
                ANSI_COLOR_NORMAL,
                CUSTOM_LINE_END
            ) : ''
        );

        $expandedLineNumbers = $this->chooseLinesLikeAPrinter($this->whatLineNumbersYouWant);
        $this->isExpanded    = ($expandedLineNumbers !== []);


        try {
            $destinations = $this->getParams()->get('destinations', []);

            if (empty($destinations)) {
                throw new DomainException(
                    'Destinations subform MUST contain at least one destination where your articles will be deployed',
                    422
                );
            }

            $computedDestinations        = new Registry($destinations);
            $computedDestinationsToArray = $computedDestinations->toArray();

            // Your Joomla! website base url
            $this->baseUrl = ArrayHelper::getColumn($computedDestinationsToArray, 'base_url', 'token_index');

            // Your Joomla! Api Token (DO NOT STORE IT IN YOUR REPO USE A VAULT OR A PASSWORD MANAGER)
            $this->token    = ArrayHelper::getColumn($computedDestinationsToArray, 'api_authtoken', 'token_index');
            $this->basePath = ArrayHelper::getColumn($computedDestinationsToArray, 'base_path', 'token_index');

            // Other Joomla articles fields
            $this->extraDefaultFieldKeys = ArrayHelper::getColumn($computedDestinationsToArray,'extra_default_fields', 'token_index');

// Add custom fields support (shout-out to Marc DECHÈVRE : CUSTOM KING)
// The keys are the columns in the csv with the custom fields names (that's how Joomla! Web Services Api work as of today)
// For the custom fields to work they need to be added in the csv and to exists in the Joomla! site.
            $this->customFieldKeys = ArrayHelper::getColumn($computedDestinationsToArray,'custom_fields', 'token_index');

            $this->deployScript();
        } catch (Throwable $e) {
            $this->consoleOutputStyle->error(
                Text::sprintf(
                    'PLG_CONSOLE_CHOCOCSV_DEPLOY_ARTICLE_COMMAND_DESCRIPTION',
                    $e->getMessage(),
                    $e->getLine(),
                    $e->getTraceAsString(),
                    $e->getPrevious() ? $e->getPrevious()->getTraceAsString() : ''
                )
            );

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    protected function configure(): void
    {
        $help = "<info>%command.name%</info>Génerer Article.
		\nUsage: <info>php %command.full_name%</info>\n";

        $this->setDescription('Génerer Article avec données synthétiques.');
        $this->setHelp($help);
    }

    public function deploy()
    {
        $this->deployScript();
    }

    private function deployScript()
    {
        try {
            $this->csvReader(
                $this->csvUrl,
                $this->silent,
                $this->expandedLineNumbers,
                $this->failedCsvLines,
                $this->successfulCsvLines,
                $this->isDone
            );
        } catch (DomainException $domainException) {
            if ($this->silent == 1) {
                $this->enqueueMessage($domainException->getMessage());
            }
        } catch (Throwable $fallbackCatchAllUncaughtException) {
            // Ignore silent mode when stumbling upon fallback exception
            $this->enqueueMessage(
                sprintf(
                    '%s Error message: %s, Error code line: %d%s%s',
                    ANSI_COLOR_RED,
                    $fallbackCatchAllUncaughtException->getMessage(),
                    $fallbackCatchAllUncaughtException->getLine(),
                    ANSI_COLOR_NORMAL,
                    CUSTOM_LINE_END
                ),
                'error'
            );
        } finally {
            $this->isDone = true;

            if (in_array($this->saveReportToFile, [1, 2], true)) {
                $errors = [];
                if (!file_exists(CSV_PROCESSING_REPORT_FILEPATH)) {
                    File::write(CSV_PROCESSING_REPORT_FILEPATH, '', true);
                }
                if (!empty($this->failedCsvLines)) {
                    $errors = ['errors' => $this->failedCsvLines];
                    if ($this->saveReportToFile === 2) {
                        File::write(CSV_PROCESSING_REPORT_FILEPATH, json_encode($errors), true);
                    }
                }
                if (($this->saveReportToFile === 1) && !empty($this->successfulCsvLines)) {
                    $success = ['success' => $this->successfulCsvLines];
                    File::write(CSV_PROCESSING_REPORT_FILEPATH, json_encode(array_merge($errors, $success)), true);
                }
            }

            $this->enqueueMessage(sprintf('Done%s', CUSTOM_LINE_END));
        }
    }

    private function enqueueMessage(string $message, string $type = 'message'): void
    {
        // Ignore empty messages
        if (empty($message)) {
            return;
        }
        if ($type === 'error') {
            $this->consoleOutputStyle->error($message);

            return;
        }
        $this->consoleOutputStyle->text($message);
    }

    private function chooseLinesLikeAPrinter(string $wantedLineNumbers = ''): array
    {
        // When strictly empty process every Csv lines (Full range)
        if ($wantedLineNumbers === '') {
            return [];
        }

        // Cut-off useless processing when single digit range
        if (strlen($wantedLineNumbers) === 1) {
            return (((int)$wantedLineNumbers) < CSV_START) ? [CSV_START] : [((int)$wantedLineNumbers)];
        }

        $commaParts = explode(',', $wantedLineNumbers);
        if (empty($commaParts)) {
            return [];
        }
        sort($commaParts, SORT_NATURAL);
        $output = [];
        foreach ($commaParts as $commaPart) {
            if (!str_contains($commaPart, '-')) {
                // First line is the header, so we MUST start at least at line 2. Hence, 2 or more
                $result1 = ((int)$commaPart) > 1 ? ((int)$commaPart) : CSV_START;
                // Makes it unique in output array
                if (!in_array($result1, $output, true)) {
                    $output[] = $result1;
                }
                // Skip to next comma part
                continue;
            }
            // maximum 1 dash "group" per comma separated "groups"
            $dashParts = explode('-', $commaPart, 2);
            if (empty($dashParts)) {
                // First line is the header, so we MUST start at least at line 2. Hence, 2 or more
                $result2 = ((int)$commaPart) > 1 ? ((int)$commaPart) : CSV_START;
                if (!in_array($result2, $output, true)) {
                    $output[] = $result2;
                }
                // Skip to next comma part
                continue;
            }
            // First line is the header, so we MUST start at least at line 2. Hence, 2 or more
            $dashParts[0] = ((int)$dashParts[0]) > 1 ? ((int)$dashParts[0]) : CSV_START;

            // First line is the header, so we MUST start at least at line 2. Hence, 2 or more
            $dashParts[1] = ((int)$dashParts[1]) > 1 ? ((int)$dashParts[1]) : CSV_START;

            // Only store one digit if both are the same in the range
            if (($dashParts[0] === $dashParts[1]) && (!in_array($dashParts[0], $output, true))) {
                $output[] = $dashParts[0];
            } elseif ($dashParts[0] > $dashParts[1]) {
                // Store expanded range of numbers
                $output = array_merge($output, range($dashParts[1], $dashParts[0]));
            } else {
                // Store expanded range of numbers
                $output = array_merge($output, range($dashParts[0], $dashParts[1]));
            }
        }
        // De-dupe and sort again at the end to tidy up everything
        $unique = array_unique($output);
        // For some reason out of my understanding sort feature in array_unique won't work as expected for me, so I do sort separately
        sort($unique, SORT_NATURAL | SORT_ASC);

        return $unique;
    }

    private function nestedJsonDataStructure(array $arr, int $isSilent = 0): array
    {
        $handleComplexValues = [];
        $iterator            = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($arr),
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        foreach ($iterator as $key => $value) {
            if (strpos($value, '{') === 0) {
                if ($isSilent == 2) {
                    $this->enqueueMessage(
                        sprintf(
                            "%s item with key: %s with value: %s%s%s",
                            ANSI_COLOR_BLUE,
                            $key,
                            $value,
                            ANSI_COLOR_NORMAL,
                            CUSTOM_LINE_END
                        )
                    );
                }
                // Doesn't seem to make sense at first but this one line allows to show intro/fulltext images and urla,urlb,urlc
                $handleComplexValues[$key] = json_decode(str_replace(["\n", "\r", "\t"], '', trim($value)));
            } elseif (json_decode($value) === false) {
                $handleComplexValues[$key] = json_encode($value);
                if ($isSilent == 2) {
                    $this->enqueueMessage(
                        sprintf(
                            "%s item with key: %s with value: %s%s%s",
                            ANSI_COLOR_BLUE,
                            $key,
                            $value,
                            ANSI_COLOR_NORMAL,
                            CUSTOM_LINE_END
                        )
                    );
                }
            } else {
                $handleComplexValues[$key] = $value;
                if ($isSilent == 2) {
                    $this->enqueueMessage(
                        sprintf(
                            "%s item with key: %s with value: %s%s%s",
                            ANSI_COLOR_BLUE,
                            $key,
                            $value,
                            ANSI_COLOR_NORMAL,
                            CUSTOM_LINE_END
                        )
                    );
                }
            }
        }

        return $handleComplexValues;
    }

    private function csvReader(
        string $url,
        int $isSilent = 1,
        array $lineRange = [],
        array &$failed = [],
        array &$successful = []
    ) {
        if (empty($url)) {
            throw new RuntimeException('Url MUST NOT be empty', 422);
        }

        $defaultKeys = [
            'id',
            'access',
            'title',
            'alias',
            'catid',
            'articletext',
            'introtext',
            'fulltext',
            'language',
            'metadesc',
            'metakey',
            'state',
            'tokenindex',
        ];

        $mergedKeys = array_unique(array_merge($defaultKeys, $this->extraDefaultFieldKeys, $this->customFieldKeys));

        // Assess robustness of the code by trying random key order
        //shuffle($mergedKeys);

        $resource = fopen($url, 'r');

        if ($resource === false) {
            throw new RuntimeException('Could not read csv file', 500);
        }

        try {
            stream_set_blocking($resource, false);

            $firstLine = stream_get_line(
                $resource,
                0,
                "\r\n"
            );

            if (!is_string($firstLine) || empty($firstLine)) {
                throw new RuntimeException('First line MUST NOT be empty. It is the header', 422);
            }

            $csvHeaderKeys        = str_getcsv($firstLine);
            $commonKeys           = array_intersect($csvHeaderKeys, $mergedKeys);
            $currentCsvLineNumber = 1;
            $isExpanded           = ($lineRange !== []);

            if ($isExpanded) {
                if (count($lineRange) === 1) {
                    $minLineNumber = $lineRange[0];
                    $maxLineNumber = $lineRange[0];
                } else {
                    // Rather than starting from 1 which is not that efficient, start from minimum value in CSV line range
                    $minLineNumber = min($lineRange);
                    $maxLineNumber = max($lineRange);
                }
            }

            while (!$isFinished && !feof($resource)) {
                $currentLine = stream_get_line(
                    $resource,
                    0,
                    "\r\n"
                );
                if (!is_string($currentLine) || empty($currentLine)) {
                    continue;
                }
                // Again, for a more efficient algorithm. Do not do unecessary processing, unless we have to.
                $isEdgeCaseSingleLineInRange = ($isExpanded && (count($lineRange) === 1));
                if (!$isExpanded || ($isExpanded && count($lineRange) > 1) || $isEdgeCaseSingleLineInRange) {
                    $currentCsvLineNumber += 1;

                    if ($isEdgeCaseSingleLineInRange && ($currentCsvLineNumber < $minLineNumber)) {
                        continue; // Continue until we reach the line we want
                    }
                }

                $extractedContent = str_getcsv($currentLine, CSV_SEPARATOR, CSV_ENCLOSURE, CSV_ESCAPE);

                // Skip empty lines
                if (empty($extractedContent)) {
                    continue;
                }

                // Allow using csv keys in any order
                $commonValues = array_intersect_key($extractedContent, $commonKeys);

                // Skip invalid lines
                if (empty($commonValues)) {
                    continue;
                }

                // Iteration on leafs AND nodes
                $handleComplexValues = $this->nestedJsonDataStructure($commonValues, $isSilent);

                try {
                    $encodedContent = json_encode(
                        array_combine($commonKeys, $handleComplexValues),
                        JSON_THROW_ON_ERROR
                    );

                    // Stop processing immediately if it goes beyond range
                    if (($isExpanded && count($lineRange) > 1) && ($currentCsvLineNumber > $maxLineNumber)) {
                        $isFinished = true;
                        throw new DomainException(
                            sprintf(
                                'Processing of CSV file done. Last line processed was line %d',
                                $currentCsvLineNumber
                            ), 200
                        );
                    }

                    if ($encodedContent === false) {
                        throw new RuntimeException('Current line seem to be invalid', 422);
                    } elseif (!$isFinished && ((is_string($encodedContent) && (($isExpanded && in_array(
                                        $currentCsvLineNumber,
                                        $lineRange,
                                        true
                                    )) || !$isExpanded)))) {
                        $this->processEachCsvLineData(['line' => $currentCsvLineNumber, 'content' => $encodedContent]);

                        // Only 1 element in range. Don't do useless processing after first round.
                        if ($isExpanded && (count(
                                    $lineRange
                                ) === 1 && ($currentCsvLineNumber === $maxLineNumber))) {
                            $isFinished = true;
                            throw new DomainException(
                                sprintf(
                                    'Processing of CSV file done. Last line processed was line %d',
                                    $currentCsvLineNumber
                                ), 200
                            );
                        }
                    }
                } catch (DomainException $domainException) {
                    $successful[$currentCsvLineNumber] = $domainException->getMessage();
                    throw $domainException;
                } catch (Throwable $encodeContentException) {
                    $failed[$currentCsvLineNumber] = [
                        'error'      => $encodeContentException->getMessage(),
                        'error_line' => $encodeContentException->getLine()
                    ]; // Store failed CSV line numbers for end report.
                    continue; // Ignore failed CSV lines
                }
            }
        } catch (DomainException $domainException) {
            if (isset($resource) && is_resource($resource)) {
                fclose($resource);
            }
            throw $domainException;
        } catch (Throwable $e) {
            if ($isSilent == 1) {
                $this->enqueueMessage(
                    sprintf(
                        "%s Error message: %s, Error code line: %d, Error CSV Line: %d%s%s",
                        ANSI_COLOR_RED,
                        $e->getMessage(),
                        $e->getLine(),
                        $currentCsvLineNumber,
                        ANSI_COLOR_NORMAL,
                        CUSTOM_LINE_END
                    ),
                    'error'
                );
            }
            if (isset($resource) && is_resource($resource)) {
                fclose($resource);
            }
            throw $e;
        } finally {
            if (isset($resource) && is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    private function processEachCsvLineData(array $dataValue)
    {
        if (empty($dataValue)) {
            return;
        }

        $dataCurrentCSVline = $dataValue['line'];
        $dataString         = $dataValue['content'];

        $decodedDataString = false;
        if (json_validate($dataString)) {
            $decodedDataString = json_decode($dataString, false, 512, JSON_THROW_ON_ERROR);
        } elseif (is_object($dataString)) {
            $decodedDataString = $dataString;
        }


        try {
            if (($decodedDataString === false) || (!isset($token[$decodedDataString->tokenindex]))
            ) {
                return;
            }

            // HTTP request headers
            $headers = [
                'Accept: application/vnd.api+json',
                'Content-Type: application/json',
                'Content-Length: ' . strlen($dataString),
                sprintf('X-Joomla-Token: %s', trim($token[$decodedDataString->tokenindex])),
            ];

            // Article primary key. Usually 'id'
            $pk = (int)$decodedDataString->id;

            $combinedHttpResponse[$dataCurrentCSVline] = $this->processHttpRequest(
                $pk ? 'PATCH' : 'POST',
                $this->endpoint(
                    $this->baseUrl[$decodedDataString->tokenindex],
                    $this->basePath[$decodedDataString->tokenindex],
                    $pk
                ),
                $dataString,
                $headers,
                self::REQUEST_TIMEOUT,
                self::USER_AGENT
            );
            $decodedJsonOutput                         = json_decode(
                $combinedHttpResponse[$dataCurrentCSVline],
                false,
                512,
                JSON_THROW_ON_ERROR
            );

            // don't show errors, handle them gracefully
            if (isset($decodedJsonOutput->errors) && !isset($storage[$dataCurrentCSVline])) {
                // If article is potentially a duplicate (already exists with same alias)
                if (isset($decodedJsonOutput->errors[0]->code) && $decodedJsonOutput->errors[0]->code === 400) {
                    // Change the alias
                    $decodedDataString->alias = StringHelper::increment(
                        StringHelper::strtolower($decodedDataString->alias),
                        'dash'
                    );
                    // Retry
                    $this->processEachCsvLineData(['line' => $dataCurrentCSVline, 'content' => $decodedDataString]);
                }
            } elseif (isset($decodedJsonOutput->data) && isset($decodedJsonOutput->data->attributes) && !isset($successfulCsvLines[$dataCurrentCSVline])) {
                if ($this->silent == 1) {
                    $successfulCsvLines[$dataCurrentCSVline] = sprintf(
                        "%s Deployed to: %s, CSV Line: %d, id: %d, created: %s, title: %s, alias: %s%s%s",
                        ANSI_COLOR_GREEN,
                        $decodedDataString->tokenindex,
                        $dataCurrentCSVline,
                        $decodedJsonOutput->data->id,
                        $decodedJsonOutput->data->attributes->created,
                        $decodedJsonOutput->data->attributes->title,
                        $decodedJsonOutput->data->attributes->alias,
                        ANSI_COLOR_NORMAL,
                        CUSTOM_LINE_END
                    );

                    $this->enqueueMessage($successfulCsvLines[$dataCurrentCSVline]);
                }
            }
        } catch (Throwable $e) {
            if ($this->silent == 1) {
                $failedCsvLines[$dataCurrentCSVline] = sprintf(
                    "%s Error message: %s, Error code line: %d, Error CSV Line: %d%s%s",
                    ANSI_COLOR_RED,
                    $e->getMessage(),
                    $e->getLine(),
                    $dataCurrentCSVline,
                    ANSI_COLOR_NORMAL,
                    CUSTOM_LINE_END
                );
                $this->enqueueMessage($failedCsvLines[$dataCurrentCSVline], 'error');
            }
        }
    }

    private function processHttpRequest(
        string $givenHttpVerb,
        string $endpoint,
        string $dataString,
        array $headers,
        int $timeout = 3
    ) {
        $uri      = (new Uri($endpoint));
        $response = $this->getHttpClient()->request(
            $givenHttpVerb,
            $uri,
            $dataString,
            $headers,
            $timeout,
            self::USER_AGENT
        );

        return (string)$response->getBody();
    }

    /**
     * This time we need endpoint to be a function to make it more dynamic
     */
    private function endpoint(string $givenBaseUrl, string $givenBasePath, int $givenResourceId = 0): string
    {
        return $givenResourceId ? sprintf(
            '%s/%s/%s/%d',
            $givenBaseUrl,
            $givenBasePath,
            'content/articles',
            $givenResourceId
        ) : sprintf('%s/%s/%s', $givenBaseUrl, $givenBasePath, 'content/articles');
    }
}
