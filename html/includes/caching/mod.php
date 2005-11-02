<?php
/**
 * File: $Id$
 * 
 * Xaraya Web Interface Entry Point
 * 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Page/Mod Caching
 * @author mikespub
 * @author jsb
 */

/**
 * Check whether a block is cached
 *
 * @access public
 * @param  array $args($cacheKey,$blockDynamics, $blockPermissions, $name = '')
 * @return bool
 */
function xarModIsCached($args)
{
    global $xarOutput_cacheCollection,
           $xarMod_cacheCode,
           $xarMod_cacheTime,
           $modCacheExpireTime,
           $xarMod_noCache;

    $xarTpl_themeDir = xarTplGetThemeDir();

    $noCache = '';

    extract($args);

    if (!xarModIsHooked('xarcachemanager', $modName)) {
        $noCache = 1;
    }

    if (!empty($noCache)) {
        $xarMod_noCache = 1;
        return false;
    }
    if (empty($userShared)) {
        $userShared = 0;
    }
    if (!isset($modCacheExpireTime)) {
        $modCacheExpireTime = $xarMod_cacheTime;
    }

    $factors = xarServerGetVar('HTTP_HOST') . $xarTpl_themeDir .
               xarUserGetNavigationLocale();

    $factors .= xarServerGetVar('REQUEST_URI');
    $param = xarServerGetVar('QUERY_STRING');
    if (!empty($param)) {
        $factors .= '?' . $param;
    }

    if ($userShared == 2) {
        $factors .= 0;
    } elseif ($userShared == 1) {
        $gidlist = xarCache_getParents();
        $factors .= join(';',$gidlist);
    } else {
        $factors .= xarSessionGetVar('uid');
    }

    if (!empty($modargs)) {
        $factors .= md5(serialize($modargs));
    }

    $xarMod_cacheCode = md5($factors);

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/mod/$cacheKey-$xarMod_cacheCode.php";

    if (
        // if the cache file exists AND
        file_exists($cache_file) &&
        // doesn't expire OR has not expired yet
        ($modCacheExpireTime == 0 ||
         filemtime($cache_file) > time() - $modCacheExpireTime)) {
        // we have it in the cache
        return true;
    } else {
        // we don't
        return false;
    }
}

function xarModGetCached($cacheKey, $name = '')
{
    global $xarOutput_cacheCollection, $xarMod_cacheCode;

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/mod/$cacheKey-$xarMod_cacheCode.php";

    $modCachedOutput = xarOutputGetCached($cache_file);

    return $modCachedOutput;
}

/**
 * Set the contents of a mod in the cache
 *
 * @access public
 * @param  string $cacheKey
 * @param  string $name
 * @param  string $value
 *
 */
function xarModSetCached($cacheKey, $name, $value)
{
    global $xarOutput_cacheCollection,
           $xarOutput_cacheSizeLimit,
           $xarMod_cacheCode,
           $modCacheExpireTime,
           $xarMod_noCache;

    if ($xarMod_noCache == 1) {
        $xarMod_noCache = '';
        return;
    }
    
    if (xarCore_IsCached('Page.Caching', 'nocache')) { return; }
    if (xarCore_IsCached('Mod.Caching', 'nocache')) { return; }

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/mod/$cacheKey-$xarMod_cacheCode.php";
    if (
        // if the http request is a get AND
        xarServerGetVar('REQUEST_METHOD') == 'GET' &&
        // the cache file doesn't exist yet, OR has an expiration time and is stale AND
        (!file_exists($cache_file) ||
         ($modCacheExpireTime != 0 &&
          filemtime($cache_file) < time() - $modCacheExpireTime)) &&
        // the cache collection directory hasn't reached its size limit
        !xarCache_SizeLimit($xarOutput_cacheCollection, 'Mod')
        ) {

        // write the contents to a file
        xarOutputSetCached($cacheKey, $cache_file, 'Mod', $value);

    }
}

?>
