<?php
/**
 * Base Router class
 *
 * @package core
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

class xarRouter extends Object
{
    protected $routes       = array();
    protected $currentRoute = 'default';
    protected $globalParams = array();
    
    public function addRoute($name, xarRoute $route) 
    {
        $this->routes[$name] = $route;        
        return true;
    }

    public function addDefaultRoutes()
    {
        if (empty($this->routes)) {
            sys::import('xaraya.structures.relativedirectoryiterator');
            $routesdir = 'xaraya/mapper/routers/routes';
            $dir = new RelativeDirectoryIterator(sys::lib() . $routesdir);
    
            $dispatcher = xarController::getDispatcher();
            
            // Loop through the routes directory
            for ($dir->rewind();$dir->valid();$dir->next()) {
                if ($dir->isDir()) continue; // no dirs
                if ($dir->getExtension() != 'php') continue; // only php files
                if ($dir->isDot()) continue; // others we don't want
    
                $file = $dir->getPathName();
                if (!isset($loaded[$file])) {
                    $filename = substr(basename($file),0,-4);
                    $route = str_replace('/','.',$routesdir . "/" . $filename);
                    try {
                        sys::import($route);
                        $classname = UCFirst($filename).'Route';
                        $this->routes[$filename] = new $classname(array(), $dispatcher);                        
                    } catch (Exception $e) {
                        throw new Exception(xarML('The file #(1) could not be loaded', $route . '.php'));
                    }
                    $loaded[$file] = true;
                }
            }
        }
        return true;
    }

    public function route(xarRequest $request)
    {
        $this->addDefaultRoutes();
        $found = false;
        foreach (array_reverse($this->routes) as $name => $route) {
            if ($route->match($request)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $name = xarConfigVars::get(null,'Site.Core.DefaultRoute');
            $route = $this->routes[$name];
        }
        $request->setRoute($route);
        $this->currentRoute = $name;
        return true;
    }

    public function assemble($userParams=array(), $name=null, $reset=false, $encode=true)
    {
        if ($name == null) {
            $name = isset($this->currentRoute) ? $this->currentRoute : 'default';
        }
        
        $params = array_merge($this->globalParams, $userParams);
        
        $route = $this->getRoute($name);
        $url   = $route->assemble($params, $reset, $encode);

        if (!preg_match('|^[a-z]+://|', $url)) {
            $url = rtrim(xarServer::getBaseURL(), xarController::$delimiter) . xarController::$delimiter . $url;
        }

        return $url;
    }

    public function getRoute($name=null)
    {
        if (null == $name) $name = $this->currentRoute;
        return $this->routes[$name];
    }

    protected function setRequestParams(xarRequest $request, $params)
    {
        foreach ($params as $key => $value) {
            if ($key === 'module') $request->module = $value;
            if ($key === 'type')   $request->type   = $value;
            if ($key === 'func')   $request->func   = $value;
        }
    }

}
?>