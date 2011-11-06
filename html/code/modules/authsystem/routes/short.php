<?php
sys::import('xaraya.mapper2.routes.base');
class AuthsystemShortRoute extends BaseRoute 
{
    protected $name = 'authsystem';
    
    public function encode(ixarUrl $url)
    {
        // when encoding, mod, type, func and args will be available from $url
        if ($url->getType() == 'admin') return;
        
        $path = $url->getPath();        
        $query = $url->getArgs();
        
        switch ($url->getFunc()) {
            case 'main':
            case 'showloginform':
                $path[1] = 'login';
            break;
            case 'login':
                $path[1] = 'auth';
            break;
            default:
                $path[1] = $url->getFunc();
            break;
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
         
         if (!empty($path[1])) {
             switch ($path[1]) {
                 case 'login':
                     $url->setFunc('showloginform');
                 break;
                 case 'auth':
                     $url->setFunc('login');
                 break;
                 default:
                     $url->setFunc($path[1]);
                 break;
             }
         } else {
             $url->setFunc('showloginform');
         }
         
         $url->setArgs($args);
         return $url;   
    }
}
?>