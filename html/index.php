<?php
/**
 * Xaraya Web Interface Entry Point
 *
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @package core
 * @subpackage Web Interface Entry Point
 * @author Marco Canini
 */

 /**
 * Load the Xaraya pre core
 */
include_once 'lib/xaraya/xarPreCore.php';

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
 * @todo <marco> #2 Do fallback if raised exception is coming from template engine
 */
function xarMain()
{
    // Load the core with all optional systems loaded
    xarCoreInit(XARCORE_SYSTEM_ALL);

    // Get module parameters
    list($modName, $modType, $funcName) = xarRequest::getInfo();

    // Default Page Title
    $SiteSlogan = xarModGetVar('themes', 'SiteSlogan');
    xarTplSetPageTitle(xarVarPrepForDisplay($SiteSlogan));

    // Theme Override
    xarVarFetch('theme','str:1:',$themeName,'',XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);
    if (!empty($themeName)) {
        $themeName = xarVarPrepForOS($themeName);
        if (xarThemeIsAvailable($themeName)){
            xarTplSetThemeName($themeName);
            xarVarSetCached('Themes.name','CurrentTheme', $themeName);
        }
    }

    // Check if page caching is enabled
    $pageCaching = 0;
    if (defined('XARCACHE_PAGE_IS_ENABLED')) {
        $pageCaching = 1;
        $cacheKey = "$modName-$modType-$funcName";
    }

    $run = 1;
    if ($pageCaching == 1 && xarPageIsCached($cacheKey,'page')) {
        // output the cached page *or* a 304 Not Modified status
        if (xarPageGetCached($cacheKey,'page')) {
            // we could return true here, but we'll continue just in case
            // processing changes below someday...
            $run = 0;
        }
    }

    if ($run) {
        // Load the module
        if (!xarMod::load($modName, $modType)) return; // throw back

        // if the debugger is active, start it
        if (xarCoreIsDebuggerActive()) {
            ob_start();
        }

        // Call the main module function
        $mainModuleOutput = xarMod::guiFunc($modName, $modType, $funcName);

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
        xarEvents::trigger('ServerRequest');

        // Note : the page template may be set to something else in the module function
        if (xarTplGetPageTemplateName() == 'default' && $modType != 'admin') {
            // NOTE: we should fallback to the way we were handling this before
            // (ie: use pages/$modName.xt if pages/user-$modName is not found)
            // instead of just switching to the new way without a deprecation period
            // so as to prevent breaking anyone's sites. <rabbitt>
            if (!xarTplSetPageTemplateName('user-'.$modName)) {
                xarTplSetPageTemplateName($modName);
            }
        }

        // Set page template
        if ($modType == 'admin' && xarTplGetPageTemplateName() == 'default' && xarModGetVar('themes', 'usedashboard')) {
            $dashtemplate=xarModGetVar('themes','dashtemplate');
            //if dashboard is enabled, use the dashboard template else fallback on the normal template override system for admin templates
              if (!xarTplSetPageTemplateName($dashtemplate.'-'.$modName)) {
                xarTplSetPageTemplateName($dashtemplate);
            }
        }elseif ($modType == 'admin' && xarTplGetPageTemplateName() == 'default') {
             // Use the admin-$modName.xt page if available when $modType is admin
            // falling back on admin.xt if the former isn't available
            if (!xarTplSetPageTemplateName('admin-'.$modName)) {
                xarTplSetPageTemplateName('admin');
            }
        }

        // Here we check for exceptions even if $res isn't empty
        if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return; // we found a non-core error

        xarVarFetch('pageName','str:1:', $pageName, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);
        if (xarServer::getVar('X-Requested-With') == 'XMLHTTPRequest' && $pageName == '') {
            xarTplSetPageTemplateName('module');
        } elseif (!empty($pageName)) {
            xarTplSetPageTemplateName($pageName);
        }

        // Render page
        //$pageOutput = xarTpl_renderPage($mainModuleOutput, NULL, $template);
        $pageOutput = xarTpl_renderPage($mainModuleOutput);

        // Handle exceptions (the bubble at the top handler
        if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return; // we found a non-core error

        if ($pageCaching == 1) {
            // save the output in cache *before* sending it to the client
            xarPageSetCached($cacheKey, 'page', $pageOutput);
        }

        echo $pageOutput;
    }

    return true;
}

xarMain();
// All done, the shutdown handlers take care of the rest
?>