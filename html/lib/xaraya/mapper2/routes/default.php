<?php
sys::import('xaraya.mapper2.routes.base');
class DefaultRoute extends BaseRoute
{
    protected $name = 'default';
    protected $dispatcher = 'default';

/**
 * Encoding
 * Default encoding for xaraya urls 
 * object urls ?object=foo[&method=bar][& ...]
 * mod urls    ?module=foo[&type=bar][&func=foobar][& ...]
**/    
    public function encode(ixarUrl $url)
    {
        // when encoding, mod, type, func and args will be available from $url    
        $module = $url->getModule();
        $type = $url->getType();
        $func = $url->getFunc();
        // we want to build an array forming the query part of this url
        $query = array();       
        if ($module == 'object') {
            // object encoding, check if type is admin 
            if ($type === 'admin') {
                // there are no admin type object functions, pass this to dd admin
                $query['module'] = 'dynamicdata';
                $query['type'] = 'admin';
            } else {
                // assume this is a standalone object, type is the name of the object
                $query['object'] = $type;
                // func is the name of the method
                if (!empty($func) && $func != 'view')
                    $query['method'] = $func;
            }
        } else {
            // module encoding 
            // use default module if none specified 
            if (empty($module)) {
                $module = xarModVars::get('modules', 'defaultmodule');
                // use default type only if default module and none specified
                if (empty($type))
                    $type = xarModVars::get('modules', 'defaultmoduletype');
                // use default type only if default module and none specified
                if (empty($func))
                    $func = xarModVars::get('modules', 'defaultmodulefunction');
            }
            $query['module'] = $module;
            if ($type != 'user')
                $query['type'] = $type;
            if ($func != 'main')
                $query['func'] = $func;
        }
        // merge any args passed to the URL function
        $query += $url->getArgs();
        // remove any query params that have default values
        if (!empty($query['module']) && 
            $query['module'] == xarModVars::get('modules', 'defaultmodule') && 
            $url->getType() == xarModVars::get('modules', 'defaultmoduletype'))
            unset($query['module']);
        if (!empty($query['type']) && $query['type'] == 'user')
            unset($query['type']);
        if (!empty($query['func']) && $query['func'] == 'main')
            unset($query['func']);
        if (!empty($query['method']) && $query['method'] == 'view')
            unset($query['method']);
        // set the query params 
        $url->setQuery($query);
        // and return the url object
        return $url;
    }

/**
 * Encoding
 * Default decoding for xaraya urls 
 * object urls ?object=foo[&method=bar][& ...]
 * mod urls    ?module=foo[&type=bar][&func=foobar][& ...]
**/     
    public function decode(ixarUrl $url)
    {
        // when decoding, path and query are available from $url
        $args = $url->getQuery();
        // we want to set module, type, func, and args based on query params
        if (!empty($args['object'])) {
            // object decoding, check if type is admin
            if (!empty($args['type']) && $args['type'] == 'admin') {
                // there are no admin type object functions, pass this to dd admin
                $url->setModule('dynamicdata');
                $url->setType('admin');
                $url->setFunc('main'); 
                $url->setDispatcher('default');
            } else {
                $url->setModule('object');
                $url->setType($args['object']);
                if (!empty($args['method'])) {
                    $url->setFunc($args['method']);
                } else {
                    $url->setFunc('view');
                }
                $url->setDispatcher('object');
            }
        } else {
            // module decoding
            if (!empty($args['module']))
                $url->setModule($args['module']);
            if (!empty($args['type']))
                $url->setType($args['type']);
            if (!empty($args['func']))
                $url->setFunc($args['func']);
            // set default module if none specified 
            if (!$url->getModule()) {
                $url->setModule(xarModVars::get('modules', 'defaultmodule'));
                // set default type only if default module and none specified
                if (!$url->getType())
                    $url->setType(xarModVars::get('modules', 'defaultmoduletype'));
                // set default func only if default module and none specified
                if (!$url->getFunc())
                    $url->setFunc(xarModVars::get('modules', 'defaultmodulefunction'));
            }
            // set defaults for any params not supplied 
            if (!$url->getType())
                $url->setType('user');
            if (!$url->getFunc())
                $url->setFunc('main');
            $url->setDispatcher('default');                
        }
        // remove params from args
        unset($args['object'], $args['method'], $args['module'], $args['type'], $args['func']);
        // set function args
        $url->setArgs($args);        
        // and return the url object
        return $url;       
    }
}
?>