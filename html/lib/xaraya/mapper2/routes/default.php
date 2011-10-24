<?php
sys::import('xaraya.mapper2.routes.base');
class DefaultRoute extends BaseRoute
{
    protected $name = 'default';
    protected $dispatcher = 'default';
    
    public function encode(ixarUrl $url)
    {
        // set default params 
        $query = array('module' => $url->getModule(), 'type' => $url->getType(), 'func' => $url->getFunc());
        // merge any function args 
        $query += $url->getArgs();
        // remove unnecessary params
        if ($url->getType() == 'user')
            unset($query['type']);
        if ($url->getFunc() == 'main')
            unset($query['func']);
        // set query params 
        $url->setQuery($query);
        return $url;
    }
    
    public function decode(ixarUrl $url)
    {
        $args = $url->getQuery();
        // try setting decoded params from query 
        if (!empty($args['module'])) {
            $url->setModule($args['module']);
            unset($args['module']);
        }
        if (!empty($args['type'])) {
            $url->setType($args['type']);
            unset($args['type']);
        }
        if (!empty($args['func'])) {
            $url->setFunc($args['func']);
            unset($args['func']);
        }
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
        // set default type if necessary
        if (!$url->getType())
            $url->setType('user');
        // set default func if necessary
        if (!$url->getFunc())
            $url->setFunc('main');                   
        // set function arguments
        $url->setArgs($args);
        return $url;        
    }
}
?>