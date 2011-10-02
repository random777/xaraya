<?php
/**
 * Default Route class
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

/**
 * This route assumes a URL of the form
 *
 * [protocol][anything] ? module=[value1]&type=[value2]&funk=[value3]...
 *
 * 1. If the stuff after ? is empty, then the default values shown below are used
 * 2. If the stuff after ? is not empty, a module param must be supplied
 * 3. If type or func params are missing in the URL, they are given the default values below
 * 4. The order of the params is irrelvant
**/

sys::import('xaraya.mapper.routers.base');

class DefaultRoute extends xarRoute
{
    public function __construct(Array $defaults=array(), xarDispatcher $dispatcher=null)
    {
        $this->defaults += array(
                            'module' => 'base',
                            'type'   => 'user',
                            'func'   => 'main',
                                );
        parent::__construct($defaults, $dispatcher);
        $this->name = "default";
    }

    public function match(xarRequest $request, $partial=false)
    {
        // Set the keys for module/type/func as per the current request, and the default values in xarController
        $this->setRequestKeys();

        // Get the request's URL string
        $path = $request->getURL();
        
        $params = array();
        
        // Parse the query part of the URL
        $urlparts = parse_url($path);
        if (empty($urlparts['query'])) return false;
        //Note that the explode depends on  &amp;
        $pairs = explode('&amp;', $urlparts['query']);
        foreach($pairs as $pair) {
            if (trim($pair) == '') continue;
            $pairparts = explode('=', $pair);
            if (empty($pairparts[1])) return false;
            $params[$pairparts[0]] = urldecode($pairparts[1]);
        }
                
        // If we don't have a module param, bail
        if (empty($params[$this->moduleKey])) return false;
        
        // Get the module
        $this->parts[$this->moduleKey] = $params[$this->moduleKey];
        unset($params[$this->moduleKey]);
        
        // Get the type; assign the default if not given
        if (empty($params[$this->typeKey])) $params[$this->typeKey] = $this->defaults[$this->typeKey];
        $this->parts[$this->typeKey] = $params[$this->typeKey];
        unset($params[$this->typeKey]);
        
        // Get the func; assign the default if not given
        if (empty($params[$this->funcKey])) $params[$this->funcKey] = $this->defaults[$this->funcKey];
        $this->parts[$this->funcKey] = $params[$this->funcKey];
        unset($params[$this->funcKey]);
        
        $this->parts['params'] = $params;
        
        return $this->routeMatched($request);
    }
}
?>