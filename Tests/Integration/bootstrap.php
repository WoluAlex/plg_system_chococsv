<?php



/**
 * Prepares a minimalist framework for unit testing.
 *
 * @package        Joomla.UnitTest
 *
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 * @link           http://www.phpunit.de/manual/current/en/installation.html
 */

// phpcs:disable PSR1.Files.SideEffects

use Joomla\CMS\Autoload\ClassLoader;
use Joomla\CMS\Version;

defined('_JEXEC') || define('_JEXEC', 1);

ini_set('error_reporting', '-1');
ini_set('log_errors_max_len', '0');
ini_set('display_errors', 'On');
ini_set('display_startup_errors', 'On');
ini_set('xdebug.mode', 'coverage,develop,debug');
ini_set('memory_limit', '1024M');

// Set fixed precision value to avoid round related issues
ini_set('precision', 14);

if (file_exists(JPATH_BASE . '/defines.php')) {
    include_once JPATH_BASE . '/defines.php';
}

require_once JPATH_BASE . '/includes/defines.php';

// Check for presence of vendor dependencies not included in the git repository
if (!file_exists(JPATH_LIBRARIES . '/vendor/autoload.php') || !is_dir(JPATH_PUBLIC . '/media/vendor')) {
    echo file_get_contents(JPATH_ROOT . '/templates/system/build_incomplete.html');

    exit;
}

require_once JPATH_BASE . '/includes/framework.php';

// Import the library loader if necessary.
if (!class_exists('JLoader')) {
    require_once JPATH_PLATFORM . '/loader.php';

    // If JLoader still does not exist panic.
    if (!class_exists('JLoader')) {
        throw new RuntimeException('Joomla Platform not loaded.');
    }
}

// Setup the autoloaders.
JLoader::setup();

// Create the Composer autoloader
/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require JPATH_LIBRARIES . '/vendor/autoload.php';

// We need to pull our decorated class loader into memory before unregistering Composer's loader
class_exists('\\Joomla\\CMS\\Autoload\\ClassLoader');

$loader->unregister();

// Decorate Composer autoloader
spl_autoload_register([new ClassLoader($loader), 'loadClass'], true, true);

// Load extension classes
require_once JPATH_LIBRARIES . '/namespacemap.php';
$extensionPsr4Loader = new JNamespacePsr4Map();
$extensionPsr4Loader->load();

// Define the Joomla version if not already defined.
defined('JVERSION') || define('JVERSION', (new Version())->getShortVersion());
