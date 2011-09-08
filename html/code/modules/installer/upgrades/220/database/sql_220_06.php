<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

sys::import('modules.installer.class.upgrade_step');

class sql_220_06 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Registering hook observers
                        ");
    }

    public function run()
    {    
        $hooks_table = xarDB::getPrefix() . '_hooks';
        $modules_table = xarDB::getPrefix() . '_modules';
        
        $dbconn  = xarDB::getConn();
        try {
            // get the list of available hooks 
            $bindvars = array();
            $query = "SELECT DISTINCT h.object, h.action, h.t_area, h.t_type,
                                      h.t_func, h.t_file, h.t_module_id,
                                      t.name, t.regid
                      FROM $hooks_table h, $modules_table t
                      WHERE h.t_module_id = t.id ";
                      
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars);    
    
            while($result->next()) {
                list($object, $action, $area, $type, $func, $file, $sysid, $modname, $regid) = $result->fields;
                $event = ucfirst($object) . ucfirst($action);
                xarHooks::registerObserver($event,$modname,$area,$type,$func);
            }
    
            $result->close();
        } catch (Exception $e) { throw($e);
            // Damn
            $this->fail();
        }
        return $this->success;
    }
}
?>