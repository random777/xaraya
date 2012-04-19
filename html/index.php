<?php
/**
 * Xaraya Web Interface Entry Point 
 *
 * @package core
 * @subpackage entrypoint
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marco Canini
 */

$GLOBALS["Xaraya_PageTime"] = microtime(true);

/**
 * Load the layout file so we know where to find the Xaraya directories
 */
$systemConfiguration = array();
include 'var/layout.system.php';
if (!isset($systemConfiguration['rootDir'])) $systemConfiguration['rootDir'] = '../';
if (!isset($systemConfiguration['libDir'])) $systemConfiguration['libDir'] = 'lib/';
if (!isset($systemConfiguration['webDir'])) $systemConfiguration['webDir'] = 'html/';
if (!isset($systemConfiguration['codeDir'])) $systemConfiguration['codeDir'] = 'code/';
$GLOBALS['systemConfiguration'] = $systemConfiguration;
if (!empty($systemConfiguration['rootDir'])) {
    set_include_path($systemConfiguration['rootDir'] . PATH_SEPARATOR . get_include_path());
}

/**
 * Load the Xaraya bootstrap so we can get started
 */
include 'bootstrap.php';

/**
 * Set up caching
 * Note: this happens first so we can serve cached pages to first-time visitors
 *       without loading the core
 */
sys::import('xaraya.caching');
// Note : we may already exit here if session-less page caching is enabled
xarCache::init();

/**
 * Load the Xaraya core
 */
sys::import('xaraya.core');

/**
 * Main Xaraya Entry
 *
 * @access public
 * @return bool
 */
function xarMain()
{
    // Load the core with all optional systems loaded
    xarCoreInit(XARCORE_SYSTEM_ALL);

    // Create the object that models this request
    $request = xarController::getRequest();
    xarController::normalizeRequest();

    // Set the theme. This happens early, because the choice of theme may influence the code
    // @todo: this belongs in the default PreDispatch observer 
    xarVarFetch('theme','str:1:',$theme,'',XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);
    // trigger pre dispatch event 
    xarMapperEvents::notify('PreDispatch');
    //xarDevice::configTheme($theme);

    // Get a cache key for this page if it's suitable for page caching
    $cacheKey = xarCache::getPageKey();

    $run = 1;
    // Check if the page is cached
    if (!empty($cacheKey) && xarPageCache::isCached($cacheKey)) {
        // Output the cached page *or* a 304 Not Modified status
        if (xarPageCache::getCached($cacheKey)) {
            // we could return true here, but we'll continue just in case
            // processing changes below someday...
            $run = 0;
        }
    }

    if ($run) {

        // if the debugger is active, start it
        if (xarCoreIsDebuggerActive()) {
            ob_start();
        }

        // Process the request
        xarController::dispatch($request);
        // Retrieve the output to send to the browser
        $mainModuleOutput = xarController::$response->getOutput();

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

        // We're all done, one ServerRequest made
        xarEvents::notify('ServerRequest');
        
        // Set page template. This happens after the code is done   
        //$device = xarDevice::getRequestingDevice();        
        //xarDevice::configPageTemplate();
        // trigger post dispatch event
        xarMapperEvents::notify('PostDispatch');

        // @todo: this belongs in the default PostDispatch observer
        xarVarFetch('pageName','str:1:', $pageName, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);

        // Render page with the output
        $pageOutput = xarTpl::renderPage($mainModuleOutput);

        // Set the output of the page in cache
        if (!empty($cacheKey)) {
            // save the output in cache *before* sending it to the client
            xarPageCache::setCached($cacheKey, $pageOutput);
        }

        echo $pageOutput;
    }

    return true;
}

// The world is not enough...
xarMain();
// All done, the shutdown handlers take care of the rest
?>
