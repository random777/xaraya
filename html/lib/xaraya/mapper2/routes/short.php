<?php
sys::import('xaraya.mapper2.routes.base');
class ShortRoute extends BaseRoute
{
    protected $name = 'short';
    protected $dispatcher = 'default';
    
    public function encode(ixarUrl $url)
    {
        // when encoding, mod, type, func and args will be available from $url    
        $module = $url->getModule();
        $type = $url->getType();
        $func = $url->getFunc();  
        // we want to build an array forming the path parts
        $path = array();        
        if ($module == 'object') {
            // object encoding, check if type is admin
            if ($type == 'admin') {
                // there are no admin type object functions, pass this to dd admin
                $path[] = 'dynamicdata';
                $path[] = 'admin';
            } else {
                // assume this is a standalone object, type is the name of the object
                $path[] = 'object';
                $path[] = $type;
                // func is the name of the method
                if ($func && $func != 'view')
                    $path[] = $func;
            }
        } else {        
            // module encoding
            // use default module if none specified 
            if (empty($module)) {
                $module = xarModVars::get('modules', 'defaultmodule');
                $url->setModule($module);
                // use default type only if default module and none specified
                if (empty($type)) {
                    $type = xarModVars::get('modules', 'defaultmoduletype');
                    $url->setType($type);
                }
                // use default type only if default module and none specified
                if (empty($func)) {
                    $func = xarModVars::get('modules', 'defaultmodulefunction');
                    $url->setFunc($func);
                }
            }
            $path[] = $module;
            // add path parts for values that aren't defaults
            if ($type && $type != 'user')
                $path[] = $type;
            if ($func && $func != 'main')
                $path[] = $func;
        }
        // query parts come from function args
        $query = $url->getArgs();
        // shouldn't be any, but just in case, remove params from query 
        unset($query['object'], $query['method'], $query['module'], $query['type'], $query['func']);
        // set the path
        $url->setPath($path);
        // set the query
        $url->setQuery($query);
        // try loading module specific route
        if ($this->loadRoute($module)) 
            $this->getRoute($module)->encode($url);
        // module encoder may have set an alias to use 
        if ($url->getModuleAlias() && $url->getModuleAlias() != $url->getModule()) {
            $path = $url->getPath();
            $path[0] = $url->getModuleAlias();
            $url->setPath($path);
        } else {
            // todo: there should be a way to define alias to use as default 
            // this was previously a combination of modvars if supplied ad hoc by module dev
        }
        // return the url object
        return $url;
    }
    
    public function decode(ixarUrl $url)
    {
        // when decoding, path and query params are available from $url
        $path = $url->getPath();
        // no path parts, not ours 
        if (empty($path)) return;
        $args = $url->getQuery();
        // ok, we have some path parts, this could be ours
        if ($path[0] == 'object') {
            // if this is really an object path we should have at least 2 path parts object/[objectname]
            if (empty($path[1])) return;
            // object decode
            if ($path[1] == 'admin') {
                // there are no admin type object functions, pass this to dd admin
                // NOTE: if this happens someone must have typed it into the browser
                $url->setModule('dynamicdata');
                $url->setType('admin');
                $url->setFunc('main');
                $url->setDispatcher('default');
            } else {
                $url->setModule('object');
                $url->setType($path[1]);
                if (!empty($path[2])) {
                    $url->setFunc($path[2]);
                } else {
                    $url->setFunc('view');
                }
                $url->setDispatcher('object');
            }
        } else {
            // module decode
            // the first path part could be the name of (or alias for) an available module 
            $alias = $path[0]; 
            $module = xarModAlias::resolve($alias);
            // if it's not a valid module, it's not ours
            if (!xarMod::isAvailable($module)) return;
            // looks like it's a shorturl
            // NOTE: the module is all we can reliably determine
            $url->setModule($module);
            $url->setModuleAlias($alias);
            if (!empty($path[1]) && ($path[1] == 'user' || $path[1] == 'admin'))
                // this is a best guess, path[1] could be any name the module route gave it
                $url->setType($path[1]);
            // set args from query params 
            $url->setArgs($args);
            // try loading module specific route
            if ($this->loadRoute($module)) 
                $this->getRoute($module)->decode($url);      
            // if mod specific route didn't set type use defaults 
            if (!$url->getType()) {
                // try to determine function type
                if ($url->getModule() == xarModVars::get('modules', 'defaultmodule')) {
                    // default module, use default type 
                    $url->setType(xarModVars::get('modules', 'defaultmoduletype'));
                } elseif (!empty($path[1]) && ($path[1] == 'user' || $path[1] == 'admin')) {
                    // this is a best guess, path[1] could be any name the module route gave it
                    $url->setType($path[1]);                
                } else {
                    // fall back 
                    $url->setType('user');
                }
            }
            // if mod specific route didn't set func use defaults 
            if (!$url->getFunc()) {
                // try to determine func
                if ($url->getModule() == xarModVars::get('modules', 'defaultmodule') &&
                    $url->getType() == xarModVars::get('modules', 'defaultmoduletype')) {
                    // default module, type use default func
                    $url->setFunc(xarModVars::get('modules', 'defaultmodulefunction'));
                } elseif (!empty($path[1])) {
                    if ($path[1] == 'user' || $path[1] == 'admin') {
                        if (!empty($path[2])) {
                            $url->setFunc($path[2]);
                        } else {
                            $url->setFunc('main');
                        }
                    } else {
                        $url->setFunc($path[1]);
                    }
                } else {
                    $url->setFunc('main');
                }
            }
            // get arguments back
            $args = $url->getArgs();
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
