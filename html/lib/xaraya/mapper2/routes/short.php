<?php
sys::import('xaraya.mapper2.routes.base');
class ShortRoute extends BaseRoute
{
    protected $name = 'short';
    protected $dispatcher = 'default';
    
    public function encode(ixarUrl $url)
    {
        $query = $url->getArgs();
        $path = array();
        $module = $url->getModule();
        // object shorturl object/method encoding
        if ($module == 'object') {
            $path[] = $url->getType();
            if ($url->getFunc() != 'view')
                $path[] = $url->getFunc();
            unset($query['object'], $query['method']);
            $url->setPath($path);
            $url->setQuery($query);
            return $url;
        }
        // try loading module specific route
        if ($this->loadRoute($module)) {
            if ($this->getRoute($module)->encode($url)) {
                return $url;
            }
        }
        // default shorturl module/[type]/[func] encoding 
        $path[] = $module;
        if ($url->getType() != 'user')
            $path[] = $url->getType();
        if ($url->getFunc() != 'main')
            $path[] = $url->getFunc();
        unset($query['module'], $query['type'], $query['func']);
        $url->setPath($path);
        $url->setQuery($query);            
        return $url;
    }
    
    public function decode(ixarUrl $url)
    {
        // we're interested in the path
        $path = $url->getPath();
        // no path parts, not ours
        if (empty($path)) return;
        // try decoding object shorturl
        if ($path[0] == 'object' && !empty($path[1])) {
            $url->setModule($path[0]);
            $url->setType($path[1]);
            if (!empty($path[2])) {
                $url->setFunc($path[2]);
                if ($path[2] == 'view')
                    unset($path[2]);
            } else {
                $url->setFunc('view');
            }
            $url->setPath($path);
            $url->setDispatcher('object');
            return $url;
        }
            
        // first path part is module or alias   
        $alias = $path[0];
        $module = xarModAlias::resolve($alias);
        // try loading module specific route
        if ($this->loadRoute($module)) {
            if ($this->getRoute($module)->decode($url)) {
                return $url;
            }
        }
        // ok, must be default encoding
        $url->setModule($module);
        // since we only ever encode 3 path parts, we know what the path might contain
        if (!empty($path[2])) {
            // matched module/type/func
            $url->setType($path[1]);
            $url->setFunc($path[2]);
            if ($path[2] == 'main')
                unset($path[2]);
            if ($path[1] == 'user')
                unset($path[1]); 
        } elseif (!empty($path[1])) {
            // matched either module/type or module/func
            // @todo: we really need to be able to discover a modules supported types  
            if ($path[1] == 'admin') {
                $url->setType($path[1]);
                $url->setFunc('main');
            } elseif ($path[1] == 'user') {
                $url->setType($path[1]);
                unset($path[1]);
                $url->setFunc('main');
            } else {
                // we're going to assume this is a func, but it could be a type (see todo)
                $url->setType('user');
                $url->setFunc($path[1]);
                if ($path[1] == 'main')
                    unset($path[1]);
            }
        } else {
            // matched nothing, use defaults
            $url->setType('user');
            $url->setFunc('main');
        }
        $url->setDispatcher('default');
        return $url;        
    }
}
?>
