<?php
/**
 * Xaraya Installer
 *
 * @package installer
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage installer
 * @author Johnny Robeson
 * @author Paul Rosania
 * @author Marc Lutolf
 * @author Marcel van der Boom
 */

/**
 * 0. basic requirements
 * ---check required extensions and PHP version
 * 1. select language
 * ---set language
 * 2. read license agreement
 * ---check agreement state
 * 3. set config.php permissions
 * ---check permissions
 * 4. input database information
 * ---verify, write config.php, install basic dataset (inc. default admin), bootstrap
 * 5. create administrator
 * ---modify administrator information in xar_users
 * 6. pick optional components
 * ---call optional components' init funcs, disable non-reusable areas of install module
 * 7. finished!
*/

/**
 * Early PHP checks
 *
 */
 
define('MYSQL_REQUIRED_VERSION', '5.0.0');
define('PHP_REQUIRED_VERSION', '5.3.0');
$xmlextension             = extension_loaded('xml');
$xslextension             = extension_loaded('xsl');

if (function_exists('version_compare')) {
    if (version_compare(PHP_VERSION,PHP_REQUIRED_VERSION,'>=')) $metRequiredPHPVersion = true;
} else {
    $metRequiredPHPVersion = false;
}
if (!$metRequiredPHPVersion
    || !$xmlextension
    || !$xslextension
    ) {
        header('Location: requirements.html');
        exit;
    }
    
/**
 * Defines for the phases
 *
 */
define ('XARINSTALL_PHASE_WELCOME',             '1');
define ('XARINSTALL_PHASE_LANGUAGE_SELECT',     '2');
define ('XARINSTALL_PHASE_LICENSE_AGREEMENT',   '3');
define ('XARINSTALL_PHASE_SYSTEM_CHECK',        '4');
define ('XARINSTALL_PHASE_SETTINGS_COLLECTION', '5');
define ('XARINSTALL_PHASE_BOOTSTRAP',           '6');

// Include the core
$systemConfiguration = array();
include 'var/layout.system.php';
if (!isset($systemConfiguration['rootDir'])) $systemConfiguration['rootDir'] = '../';
if (!isset($systemConfiguration['libDir'])) $systemConfiguration['libDir'] = 'lib/';
if (!isset($systemConfiguration['webDir'])) $systemConfiguration['webDir'] = 'html/';
if (!isset($systemConfiguration['codeDir'])) $systemConfiguration['codeDir'] = 'code/';
$GLOBALS['systemConfiguration'] = $systemConfiguration;
set_include_path($systemConfiguration['rootDir'] . PATH_SEPARATOR . get_include_path());
include 'bootstrap.php';
sys::import('xaraya.caching');
sys::import('xaraya.core');

// Besides what we explicitly load, we dont want to load
// anything extra for maximum control
$whatToLoad = XARCORE_SYSTEM_NONE;

// Start Exception Handling System very early
sys::import('xaraya.exceptions');
/*
    As long as we are coming in through install.php we need to pick up the
    bones if something goes wrong, so set the handler to bone for now
*/
set_exception_handler(array('ExceptionHandlers','bone'));

// Enable debugging always for the installer
xarCoreActivateDebugger(XARDBG_ACTIVE | XARDBG_EXCEPTIONS | XARDBG_SHOW_PARAMS_IN_BT);

// Include some extra functions, as the installer is somewhat special
// for loading gui and api functions
sys::import('modules.installer.functions');

// Basic systems always loaded
sys::import('xaraya.log');
sys::import('xaraya.events');
sys::import('xaraya.variables');
sys::import('xaraya.server');
sys::import('xaraya.mls');
sys::import('xaraya.templates');
sys::import('xaraya.mapper.main');

// Start Logging Facilities as soon as possible
$systemArgs = array();
xarLog_init($systemArgs);

// Start HTTP Protocol Server/Request/Response utilities
$systemArgs = array('enableShortURLsSupport' =>false,
                    'defaultModuleName'      => 'installer',
                    'defaultModuleType'      => 'admin',
                    'defaultModuleFunction'  => 'main',
                    'generateXMLURLs'        => false);
xarServer::init($systemArgs);
xarController::init($systemArgs);
//xarResponse::init($systemArgs);

// Start BlockLayout Template Engine
// This is probably the trickiest part, but we want the installer
// templateable too obviously
$systemArgs = array('enableTemplatesCaching' => false,
                    'themesBaseDirectory'    => 'themes',
                    'defaultThemeDir'        => 'installer',
                    'generateXMLURLs'        => false);
xarTpl_init($systemArgs);


// Get the install language everytime we request install.php
// We need the var to be able to initialize MLS, but we need MLS to get the var
// So we need something temporarily set, so we can continue
// We set a utf locale intially, otherwise the combo box wont be filled correctly
// for language names which include utf characters
$GLOBALS['xarMLS_mode'] = 'SINGLE';
xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

// Construct an array of the available locale folders
$locale_dir = sys::varpath() . '/locales/';
$allowedLocales = array();
if(is_dir($locale_dir)) {
    if ($dh = opendir($locale_dir)) {
        while (($file = readdir($dh)) !== false) {
            // Exclude the current, previous and the Bitkeeper folder
            // (just for us to be able to test, wont affect users who use a build)
            if($file == '.' || $file == '..' || filetype($locale_dir . $file) == 'file' ) continue;
            if(filetype(realpath($locale_dir . $file)) == 'dir' &&
               file_exists(realpath($locale_dir . $file . '/locale.xml'))) {
                $allowedLocales[] = $file;
            }
        }
        closedir($dh);
    }
}

if (empty($allowedLocales)) {
    throw new Exception("The var directory is corrupted: no locale was found!");
}
// A sorted combobox is better
sort($allowedLocales);

// Start Multi Language System
$systemArgs = array('translationsBackend' => 'xml2php',
                    'MLSMode'             => 'BOXED',
                    'defaultLocale'       => $install_language,
                    'allowedLocales'      => $allowedLocales);
xarMLS_init($systemArgs);

/**
 * Entry function for the installer
 *
 * @access private
 * @param phase integer the install phase to load
 * @return bool true on success, false on failure
 * @todo <johnny> use non caching templates until they are set to yes
 */
function xarInstallMain()
{
    // let the system know that we are in the process of installing
    xarVarSetCached('installer','installing',1);

    // Make sure we can render a page
    xarTplSetPageTitle(xarML('Xaraya installer'));
    if(!xarTplSetThemeName('installer'))
        throw new Exception('You need the installer theme if you want to install Xaraya.');

    // Handle installation phase designation
    xarVarFetch('install_phase','int:1:6',$phase,1,XARVAR_NOT_REQUIRED);

    // Build function name from phase
    $funcName = 'phase' . $phase;

    // if the debugger is active, start it
    if (xarCoreIsDebuggerActive()) {
       ob_start();
    }

    // Set the default page title before calling the module function
    xarTplSetPageTitle(xarML("Installing Xaraya"));

    // Run installer function
    $mainModuleOutput = xarInstallFunc($funcName);

    if (xarCoreIsDebuggerActive()) {
        if (ob_get_length() > 0) {
            $rawOutput = ob_get_contents();
            $mainModuleOutput = 'The following lines were printed in raw mode by module, however this
                                 should not happen. The module is probably directly calling functions
                                 like echo, print, or printf. Please modify the module to exclude direct output.
                                 The module is violating Xaraya architecture principles.<br /><br />'.
                                 $rawOutput.
                                 '<br /><br />This is the real module output:<br /><br />'.
                                 $mainModuleOutput;
        }
        ob_end_clean();
    }

    // Render page using the installer.xt page template
    $pageOutput = xarTpl_renderPage($mainModuleOutput,'default');

    echo $pageOutput;
    return true;
}

xarInstallMain();
?>
