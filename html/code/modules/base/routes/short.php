<?php
sys::import('xaraya.mapper2.routes.base');
class BaseShortRoute extends BaseRoute 
{
    protected $name = 'base';

    public function encode(ixarUrl $url)
    {
        // when encoding, mod, type, func and args will be available from $url
        if ($url->getType() == 'admin') return;
        
        $path = $url->getPath();        
        $query = $url->getArgs();
        if (!empty($query['page'])) {
            $path[] = $query['page'];
            unset($query['page']);
        }
        
        $url->setPath($path);
        $url->setQuery($query);
        return $url;                
    }
    
    public function decode(ixarUrl $url)
    {
         if ($url->getType() == 'admin') return;
         
         $path = $url->getPath();
         $args = $url->getQuery();
         if (!empty($path[1]))
             $args['page'] = $path[1];
         
         $url->setArgs($args);
         return $url;   
    }
}
?>