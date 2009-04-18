<?php
/**
 * Xaraya Web Interface Entry Point
 *
 * @package core
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage Page/Block Caching
 * @author mikespub
 * @author jsb
 */

/**
 * Initialise the caching options
 *
 * @return bool
 * @todo consider the use of a shutdownhandler for cache maintenance
 * @todo get rid of globals
 */
function xarCache_init($args = false)
{
    $cachingConfiguration = array();

    if (!empty($args)) {
        extract($args);
    }

    global $xarOutput_cacheCollection;
    global $xarOutput_cacheTheme;
    global $xarOutput_cacheSizeLimit;

    $xarVarDir = sys::varpath();

    if (!isset($cacheDir)) {
        $cacheDir = $xarVarDir . '/cache/output';
    }

    // load the caching configuration
    // FIXME: can we get rid of the @ ?
    if (@!include($xarVarDir . '/cache/config.caching.php')) {
        // if the config file is missing, turn caching off
        @unlink($cacheDir . '/cache.touch');
        return false;
    }

    $xarOutput_cacheCollection = realpath($cacheDir);
    $xarOutput_cacheTheme = isset($cachingConfiguration['Output.DefaultTheme']) ?
        $cachingConfiguration['Output.DefaultTheme'] : '';
    $xarOutput_cacheSizeLimit = isset($cachingConfiguration['Output.SizeLimit']) ?
        $cachingConfiguration['Output.SizeLimit'] : 2097152;

    if (file_exists($cacheDir . '/cache.pagelevel')) {
        define('XARCACHE_PAGE_IS_ENABLED',1);
        sys::import('xaraya.caching.page');
        // Note : we may already exit here if session-less page caching is enabled
        xarPageCache_init($cachingConfiguration);
    }

    if (file_exists($cacheDir . '/cache.blocklevel')) {
        define('XARCACHE_BLOCK_IS_ENABLED',1);
        sys::import('xaraya.caching.block');
        xarBlockCache_init($cachingConfiguration);
    }

    define('XARCACHE_IS_ENABLED',1);
    return true;
}

/**
 * Set the contents of some output in the cache
 *
 * @access public
 * @param  string $cacheKey
 * @param  string $cache_file
 * @param  string $cacheType
 * @param  string $value
 * @deprec 2005-02-01
 */
function xarOutputSetCached($cacheKey, $cache_file, $cacheType, $value)
{
    if (empty($GLOBALS['xar' . $cacheType . '_cacheStorage'])) {
        return;
    }

    $GLOBALS['xar' . $cacheType . '_cacheStorage']->setCached($cacheKey, $value);
}

/**
 * delete a cached file
 *
 * @access public
 * @param string $cacheKey the key identifying the particular cache you want to
 *                         access
 * @param string $name     the name of the file in that particular cache
 * @returns void
 * @deprec 2005-02-01
 */
function xarOutputDelCached($cacheKey, $name)
{
}

/**
 * flush a particular cache (e.g. when a new item is created)
 *
 * @access  public
 * @param   string $cacheKey the key identifying the particular cache you want
 *                           to wipe out
 * @returns void
 * @deprec 2005-02-01
 */
function xarOutputFlushCached($cacheKey, $dir = false)
{
    if (empty($dir)) {
        if (function_exists('xarPageFlushCached')) {
            xarPageFlushCached($cacheKey);
        }
        if (function_exists('xarBlockFlushCached')) {
            xarBlockFlushCached($cacheKey);
        }

// TODO: find out where this is called with a directory and replace

    } elseif (preg_match('/page\/?$/',$dir)) {
        if (function_exists('xarPageFlushCached')) {
            xarPageFlushCached($cacheKey);
        }

    } elseif (preg_match('/block\/?$/',$dir)) {
        if (function_exists('xarBlockFlushCached')) {
            xarBlockFlushCached($cacheKey);
        }

    } else {
        if (function_exists('xarPageFlushCached')) {
            xarPageFlushCached($cacheKey);
        }
        if (function_exists('xarBlockFlushCached')) {
            xarBlockFlushCached($cacheKey);
        }
    }
}

/**
 * clean the cache of old entries
 * note: for blocks, this only gets called when the cache size limit has been
 *       reached, and when called by blocks, all cached blocks are flushed.
 *
 * @access  protected
 * @param   string $cacheType
 * @returns void
 * @deprec 2005-02-01
 */
function xarCache_CleanCached($cacheType)
{
    if (empty($GLOBALS['xar' . $cacheType . '_cacheStorage'])) {
        return;
    }

// CHECKME: see if this is still needed
    // If the cache type is Block, then the cache is full so we flush the blocks
    // to make more room
    if ($cacheType == 'Block') {
        $GLOBALS['xar' . $cacheType . '_cacheStorage']->flushCached('');
    }

    $GLOBALS['xar' . $cacheType . '_cacheStorage']->cleanCached();
}

/**
 * helper function to determine if the cache size limit has been reached
 *
 * @access protected
 * @param  string  $dir
 * @param  string  $cacheType
 * @return boolean
 * @author jsb
 * @deprec 2005-02-01
 */
function xarCache_SizeLimit($dir = false, $cacheType)
{
    if (empty($cacheType) || empty($GLOBALS['xar' . $cacheType . '_cacheStorage'])) {
        return;
    }
    $value = $GLOBALS['xar' . $cacheType . '_cacheStorage']->sizeLimitReached();
    return $value;
}

/**
 * calculate the size of the cache
 *
 * @access public
 * @param  string  $dir
 * @param  string  $cacheType
 * @return float
 * @author nospam@jusunlee.com
 * @author laurie@oneuponedown.com
 * @author jsb
 * @todo   $dir changes type
 * @deprec 2005-02-01
 */
function xarCacheGetDirSize($dir = false)
{
    if (empty($dir)) {
        return 0;

    } elseif (preg_match('/page\/?$/',$dir)) {
        $size = $GLOBALS['xarPage_cacheStorage']->getCacheSize();

    } elseif (preg_match('/block\/?$/',$dir)) {
        $size = $GLOBALS['xarBlock_cacheStorage']->getCacheSize();

    } elseif (preg_match('/mod\/?$/',$dir)) {
        $size = 0;

    } elseif (preg_match('/output\/?$/',$dir)) {
        $size = $GLOBALS['xarPage_cacheStorage']->getCacheSize();
        $size += $GLOBALS['xarBlock_cacheStorage']->getCacheSize();
    }

    return $size;
}

/**
 * get the parent group ids of the current user (with minimal overhead)
 *
 * @access private
 * @return array of parent gids
 * @todo avoid DB lookup by passing groups via cookies ?
 * @todo Note : don't do this if admins get cached too :)
 */
function xarCache_getParents()
{
    $currentid = xarSessionGetVar('role_id');
    if (xarCore::isCached('User.Variables.'.$currentid, 'parentlist')) {
        return xarCore::getCached('User.Variables.'.$currentid, 'parentlist');
    }
    $rolemembers = xarDB::getPrefix() . '_rolemembers';
    $dbconn = xarDB::getConn();
    $query = "SELECT parentid FROM $rolemembers WHERE id = ?";
    $stmt   = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery(array($currentid));

    $gidlist = array();
    while($result->next()) {
        $gidlist[] = $result->getInt(1);
    }
    $result->Close();
    xarCore::setCached('User.Variables.'.$currentid, 'parentlist',$gidlist);
    return $gidlist;
}

/**
 * Get a storage class instance for some type of cached data
 *
 * @access protected
 * @param string $storage the storage you want (filesystem, database or memcached)
 * @param string $type the type of cached data (page, block, template, ...)
 * @param string $cachedir the cache directory
 * @param string $code the cache code (for URL factors et al.) if it's fixed
 * @param string $expire the expiration time for this data
 * @return object storage class
 */
function xarCache_getStorage($args)
{
    sys::import('xaraya.caching.storage');
    switch ($args['storage'])
    {
        case 'database':
            sys::import('xaraya.caching.storage.database');
            $classname = 'xarCache_Database_Storage';
            break;

        case 'memcached':
            if (extension_loaded('memcache')) {
                sys::import('xaraya.caching.storage.memcached');
                $classname = 'xarCache_MemCached_Storage';
            } else {
                sys::import('xaraya.caching.storage.filesystem');
                $classname = 'xarCache_FileSystem_Storage';
            }
            break;

        case 'mmcache':
            if (function_exists('mmcache')) {
                sys::import('xaraya.caching.storage.mmcache');
                $classname = 'xarCache_MMCache_Storage';
            } else {
                sys::import('xaraya.caching.storage.filesystem');
                $classname = 'xarCache_FileSystem_Storage';
            }
            break;

        case 'eaccelerator':
            if (function_exists('eaccelerator')) {
                sys::import('xaraya.caching.storage.eaccelarator');
                $classname = 'xarCache_eAccelerator_Storage';
            } else {
                sys::import('xaraya.caching.storage.filesystem');
                $classname = 'xarCache_FileSystem_Storage';
            }
            break;

        case 'filesystem':
        default:
            sys::import('xaraya.caching.storage.filesystem');
            $classname = 'xarCache_FileSystem_Storage';
            break;
    }
    return new $classname($args);
}

?>
