<?php
sys::import('xaraya.mapper2.routes.base');
class DynamicdataShortRoute extends BaseRoute 
{
    protected $name = 'dynamicdata';

    protected static $mod2name;
    protected static $name2mod;
    
    public function __construct()
    {
        sys::import('modules.dynamicdata.class.objects.master');
        $objects = DataObjectMaster::getObjects();
        foreach ($objects as $object) {
            self::$mod2name[$object['moduleid'].':'.$object['itemtype']] = $object['name'];
            self::$name2mod[$object['name']] = array(
                'module_id' => $object['moduleid'], 'itemtype' => $object['itemtype']);
        } 
    }

    public function encode(ixarUrl $url)
    {
        // when encoding, mod, type, func and args will be available from $url
        if ($url->getType() == 'admin') return;
        
        $path = $url->getPath();        
        $query = $url->getArgs();

        // no short URLs for this one...        
        if (!empty($query['table'])) return;
                
        switch ($url->getFunc()) {
            case 'main':
            default:
                break;
            case 'view':
                if (!empty($query['name']) && !empty(self::$name2mod[$query['name']])) {
                    $name = $query['name'];
                    unset($query['name']);
                } else {
                    $module_id = !empty($query['module_id']) ? $query['module_id'] : 
                                 xarMod::getRegID('dynamicdata');
                    $itemtype = !empty($query['itemtype']) ? $query['itemtype'] : 0;             
                    if (!empty(self::$mod2name[$module_id.':'.$itemtype])) {
                        $name = self::$mod2name[$module_id.':'.$itemtype];
                        unset($query['module_id'], $query['itemtype']);
                    }
                }
                if (!empty($name)) {
                    $alias = xarModAlias::resolve($name);
                    if ($this->name == $alias) {
                        // OK, we can use a 'fake' module name here
                        $url->setModuleAlias($name);
                        $path = array();
                    } else {
                        $path[] = $name;
                    }
                    if (!empty($query['catid'])) {
                        $path[] = 'c' . $catid;
                        unset($query['catid']);
                    }         
                }
                break;
            case 'display':
                if (!empty($query['itemid'])) {
                    if (!empty($query['name']) && !empty(self::$name2mod[$query['name']])) {
                        $name = $query['name'];
                        unset($query['name']);
                    } else {
                        $module_id = !empty($query['module_id']) ? $query['module_id'] : 
                                     xarMod::getRegID('dynamicdata');
                        $itemtype = !empty($query['itemtype']) ? $query['itemtype'] : 0;             
                        if (!empty(self::$mod2name[$module_id.':'.$itemtype])) {
                            $name = self::$mod2name[$module_id.':'.$itemtype];
                            unset($query['module_id'], $query['itemtype']);
                        }
                    }
                    if (!empty($name)) {
                        $alias = xarModAlias::resolve($name);
                        if ($this->name == $alias) {
                            // OK, we can use a 'fake' module name here
                            $url->setModuleAlias($name);
                            $path = array();
                        } else {
                            $path[] = $name;
                        }
                        $path[] = $query['itemid'];
                        unset($query['itemid']);
                    }
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
        
        // see if this is an alias we faked earlier
        if ($url->getModuleAlias() && $url->getModuleAlias() != $this->name) {
            $name = $url->getModuleAlias();
            if (!empty(self::$name2mod[$name])) {
                $args['name'] = $name;
                if (!empty($path[1])) {
                    if (preg_match('/^c(_?[0-9 +-]+)/',$path[1],$matches)) {
                        $url->setFunc('view');
                        $args['catid'] = $matches[1];
                    } elseif (is_numeric($path[1])) {
                        $url->setFunc('display');
                        $args['itemid'] = $path[1];
                    }
                } else {
                    $url->setFunc('view');
                }
            } 
         } elseif (!empty($path[1])) {
             switch ($path[1]) {
                 case 'main':
                 default:                         
                     break;
                 case 'view':
                     if (!empty($path[2]) && !empty(self::$name2mod[$path[2]])) {
                         $url->setFunc('view');
                         $args['name'] = $path[2];
                         if (!empty($path[3]) && preg_match('/^c(_?[0-9 +-]+)/',$path[3],$matches))
                             $args['catid'] = $matches[1];
                     }                 
                     break;
                 case 'display':
                     if (!empty($path[2]) && !empty(self::$name2mod[$path[2]])) {
                         $url->setFunc('display');
                         $args['name'] = $path[2];
                         if (!empty($path[3]) && is_numeric($path[3]))
                             $args['itemid'] = $path[3];
                     }
                     break;

             }
         } else {

         }
         $url->setArgs($args);
         return $url;   
    }
}
?>