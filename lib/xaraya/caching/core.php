<?php
/**
 * Xaraya Core Cache
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage caching
 * @author mikespub
 * @author jsb
 */

class xarCoreCache extends Object
{
    private static $cacheCollection = array();
    private static $cacheStorage = null;
    private static $isBulkStorage = 0;

    /**
     * Initialise the caching options
     *
     * @return bool
     */
    public static function init($args = false)
    {
        return true;
    }

    /**
     * Check if a variable value is cached
     *
     * @param scope string the scope identifying which part of the cache you want to access
     * @param name string the name of the variable in that particular scope
     * @return mixed value of the variable, or false if variable isn't cached
     * @todo make sure we can make this protected
    **/
    public static function isCached($scope, $name)
    {
        if (!isset(self::$cacheCollection[$scope])) {
            // initialize the cache if necessary
            self::$cacheCollection[$scope] = array();
        }
        if (isset(self::$cacheCollection[$scope][$name])) {
            return true;

        // cache storage typically only works with a single cache namespace, so we add our own scope prefix here
        } elseif (isset(self::$cacheStorage) && empty(self::$isBulkStorage) && self::$cacheStorage->isCached($scope.':'.$name)) {
            // pre-fetch the value from second-level cache here (if we don't load from bulk storage)
            self::$cacheCollection[$scope][$name] = self::$cacheStorage->getCached($scope.':'.$name);
            return true;
        }
        return false;
    }

    /**
     * Get the value of a cached variable
     *
     * @param scope string the scope identifying which part of the cache you want to access
     * @param name string the name of the variable in that particular scope
     * @return mixed value of the variable, or null if variable isn't cached
     * @todo make sure we can make this protected
    **/
    public static function getCached($scope, $name)
    {
        if (!isset(self::$cacheCollection[$scope][$name])) {
            // don't fetch the value from second-level cache here
            return;
        }
        return self::$cacheCollection[$scope][$name];
    }

    /**
     * Set the value of a cached variable
     *
     * @param scope string the scope identifying which part of the cache you want to access
     * @param name string the name of the variable in that particular scope
     * @param value string the new value for that variable
     * @return void
     * @todo make sure we can make this protected
    **/
    public static function setCached($scope, $name, $value)
    {
        if (!isset(self::$cacheCollection[$scope])) {
            // initialize cache if necessary
            self::$cacheCollection[$scope] = array();
        }
        self::$cacheCollection[$scope][$name] = $value;
        if (isset(self::$cacheStorage) && empty(self::$isBulkStorage)) {
            // save the value to second-level cache here
            self::$cacheStorage->setCached($scope.':'.$name, $value);
        }
    }

    /**
     * Delete a cached variable
     *
     * @param scope string the scope identifying which part of the cache you want to access
     * @param name string the name of the variable in that particular scope
     * @return null
     * @todo remove the double whammy
     * @todo make sure we can make this protected
    **/
    public static function delCached($scope, $name)
    {
        if (isset(self::$cacheCollection[$scope][$name])) {
            unset(self::$cacheCollection[$scope][$name]);
        }
        if (isset(self::$cacheStorage) && empty(self::$isBulkStorage)) {
            // delete the value from second-level cache here
            self::$cacheStorage->delCached($scope.':'.$name);
        }
    }

    /**
     * Flush a particular cache (e.g. for session initialization)
     *
     * @param scope string the scope identifying which part of the cache you want to wipe out
     * @return null
     * @todo make sure we can make this protected
    **/
    public static function flushCached($scope)
    {
        if(isset(self::$cacheCollection[$scope])) {
            unset(self::$cacheCollection[$scope]);
        }
        if (isset(self::$cacheStorage) && empty(self::$isBulkStorage)) {
            // CHECKME: not all cache storage supports this in the same way !
            self::$cacheStorage->flushCached($scope.':');
        }
    }

    /**
     * Set second-level cache storage if you want to keep values for longer than the current HTTP request
     *
     * @param  cacheStorage the cache storage instance you want to use (typically in-memory like apc, memcached, xcache, ...)
     * @param  cacheExpire how long do you want to keep values in second-level cache storage (if the storage supports it)
     * @param  isBulkStorage do we load/save all variables in bulk by scope or not ?
     * @return null
    **/
    public static function setCacheStorage($cacheStorage, $cacheExpire = 0, $isBulkStorage = 0)
    {
        self::$cacheStorage = $cacheStorage;
        self::$cacheStorage->setExpire($cacheExpire);
        // Make sure we use type 'core' for the cache storage here
        if (empty(self::$cacheStorage->type) || self::$cacheStorage->type != 'core') {
            self::$cacheStorage->type = 'core';
            // Update the global namespace and prefix of the cache storage
            self::$cacheStorage->setNamespace(self::$cacheStorage->namespace);
        }
        // see what's going on in the cache storage ;-)
        //self::$cacheStorage->logfile = sys::varpath() . '/logs/core_cache.txt';
        // FIXME: some in-memory cache storage requires explicit garbage collection !?

        self::$isBulkStorage = $isBulkStorage;
        if ($isBulkStorage) {
            // load from second-level cache storage here
            self::loadBulkStorage();
            // save to second-level cache storage at shutdown
            register_shutdown_function(array('xarCoreCache','saveBulkStorage'));
        }
    }

// CHECKME: work with bulk load / bulk save per scope instead of individual gets per scope:name ?
//          But what about concurrent updates in bulk then (+ unserialize & autoload too early) ?
//          There doesn't seem to be a big difference in performance using bulk or not, at least with xcache
    public static function loadBulkStorage()
    {
        if (!isset(self::$cacheStorage) || empty(self::$isBulkStorage)) return;
        // get the list of scopes
        if (!self::$cacheStorage->isCached('__scopelist__')) return;
        $scopelist = self::$cacheStorage->getCached('__scopelist__');
        if (empty($scopelist)) return;
        // load each scope from second-level cache
        foreach ($scopelist as $scope) {
            $value = self::$cacheStorage->getCached($scope);
            if (!empty($value)) {
                self::$cacheCollection[$scope] = unserialize($value);
            }
        }
    }

    public static function saveBulkStorage()
    {
        if (!isset(self::$cacheStorage) || empty(self::$isBulkStorage)) return;
        // get the list of scopes
        $scopelist = array_keys(self::$cacheCollection);
        self::$cacheStorage->setCached('__scopelist__', $scopelist);
        // save each scope to second-level cache
        foreach ($scopelist as $scope) {
            $value = serialize(self::$cacheCollection[$scope]);
            self::$cacheStorage->setCached($scope, $value);
        }
    }
}

?>