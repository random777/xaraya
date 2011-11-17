<?php
sys::import('xaraya.mapper2.routes.base');
class RolesShortRoute extends BaseRoute 
{
    protected $name = 'roles';

    public function encode(ixarUrl $url)
    {
        // when encoding, mod, type, func and args will be available from $url
        if ($url->getType() == 'admin') return;
        
        $path = $url->getPath();        
        $query = $url->getArgs();
        
        switch ($url->getFunc()) {
            case 'main':
            default:
                break;
            case 'view':
                $path[1] = 'list';
                if (!empty($query['phase']) && $query['phase'] == 'viewall') {
                    $path[] = $query['phase'];
                    unset($query['phase']);
                }
                if (!empty($query['letter'])) {
                    $path[] = $query['letter'];
                    unset($query['letter']);
                }
                break;
            case 'lostpassword':
                $path[1] = 'password';
                break;
            case 'account':
                $path[1] = 'account';
                if (!empty($query['tab'])){
                    switch ($query['tab']) {
                        case 'basic':
                            $path[] = 'edit';
                            unset($query['tab']);
                            break; 
                        default: 
                            $path[] = $query['tab'];
                            unset($query['tab']);
                            break; 
                    }
                }                  
                break;
            case 'usermenu':
                $path[1] = 'settings';
                if (!empty($query['phase']) && ($query['phase'] == 'formbasic' || $query['phase'] == 'form')) {
                    // Note : this URL format is no longer in use
                    unset($query['phase']);
                    $path[] = 'form';
                }
                break;

            case 'display':
                // check for required parameters
                if (isset($query['id']) && is_numeric($query['id'])) {
                    $path[1] = $query['id'];
                    unset($query['id']);
                }
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
                 case 'list':
                     $url->setFunc('view');
                     if (empty($path[2]) || $path[2] == 'viewall') {
                         $args['phase'] = 'viewall';
                         if (!empty($path[3]))
                             $args['letter'] = $path[3];
                     } else {
                         $args['letter'] = $path[2];
                     }
                     break;
                 case 'password':
                     $url->setFunc('lostpassword');
                     break;
                 case 'account':
                     
                     $url->setFunc('account');
                     if (!empty($path[2])) {
                         switch ($path[2]) {
                             case 'edit':
                                 $args['tab'] = 'basic';
                                 break;
                             case 'profile':
                                 $args['tab'] = 'profile';
                                 break;
                             default:
                                 $args['loadmodule'] = $path[2];
                                 break;
                         }
                     }
                     break;
                 default:
                     if (is_numeric($path[1])) {
                         $url->setFunc('display');
                         $args['id'] = $path[1];
                     } else {
                         $url->setFunc('account');
                     }
                     break;
             }
         } else {
             $url->setFunc('account');
         }
         $url->setArgs($args);
         return $url;   
    }
}
?>