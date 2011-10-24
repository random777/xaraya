<?php
sys::import('xaraya.mapper2.routes.base');
class ObjectRoute extends BaseRoute
{
    protected $name = 'object';
    protected $dispatcher = 'object';
    
    public function encode(ixarUrl $url)
    {
        // make sure this is ours to encode 
        if ($url->getModule() != 'object') return;
        // set default params 
        $query = array('object' => $url->getType(), 'method' => $url->getFunc());
        // merge any function args 
        $query += $url->getArgs();
        // remove unnecessary params
        if ($url->getFunc() == 'view')
            unset($query['method']);        
        // set query params 
        $url->setQuery($query);
        return $url;
    }
    
    public function decode(ixarUrl $url)
    {
        // get params (if any)
        $args = $url->getQuery();
        // no query params, not ours
        if (empty($args)) return;
        // no object param, not ours 
        if (empty($args['object'])) return;
        // ok, looks like we're responsible for this url
        $url->setRoute('object');
        $url->setModule('object');
        $url->setType($args['object']);
        if (!empty($args['method'])) {
            $url->setFunc($args['method']);
            unset($args['method']);
        } else {
            $url->setFunc('view');
        }
        $url->setArgs($args);
        return $url;        
    }
}
?>