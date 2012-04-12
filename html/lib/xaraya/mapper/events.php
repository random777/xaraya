<?php
sys::import('xaraya.events');
class xarMapperEvents extends xarEvents 
{
    // unique event system itemtype ids for storage/retrieval/actioning in the event system 
    const MAPPER_SUBJECT_TYPE  = 5;    
    const MAPPER_OBSERVER_TYPE = 6;
/**    
 * required functions, provide event system with late static bindings for these values
**/
    public static function getSubjectType()
    {
        return xarMapperEvents::MAPPER_SUBJECT_TYPE;
    }    
    public static function getObserverType()
    {
        return xarMapperEvents::MAPPER_OBSERVER_TYPE;
    }
    /**
     * public event registration functions
     *
    **/    
    public static function registerSubject($event,$scope,$module,$area='class',$type='mappersubjects',$func='notify')
    {
        return xarMapperEvents::register($event, $module, $area, $type, $func, xarMapperEvents::MAPPER_SUBJECT_TYPE, $scope);
    }    
    
    public static function registerObserver($event,$module,$area='class',$type='mapperobservers',$func='notify')
    {       
        return xarMapperEvents::register($event, $module, $area, $type, $func, xarMapperEvents::MAPPER_OBSERVER_TYPE);
    } 

    
    public static function getObserverFiles()
    {
        $subjects = self::getSubjects();
        $files = array();        
        if (!empty($subjects)) {
            $events = array_keys($subjects);
            $modules = xarMod::apiFunc('modules', 'admin', 'getitems',
                array(
                    'state' => XARMOD_STATE_ACTIVE,
                ));
            $basePath = sys::code() . 'modules';
            foreach ($modules as $module) {
                $dirPath = "{$basePath}/{$module['name']}/class/mapperobservers";
                if (file_exists($dirPath) && is_dir($dirPath)) {
                    foreach ($events as $event) {
                        $filePath = $dirPath . '/' . strtolower($event) . '.php';
                        if (file_exists($filePath) && is_readable($filePath)) {
                            $files[] = array(
                                'path' => $filePath,
                                'module' => $module['name'],
                                'event' => $event,
                            );
                        }
                    }
                }
            }
        }
        return $files;
    }

}
?>