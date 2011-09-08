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

class sql_230_06 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Adding a class column to the themes table
                        ");
    }

    public function run()
    {        
        // Define parameters
        $table = xarDB::getPrefix() . '_themes';
        
        // Run the query
        $dbconn  = xarDB::getConn();
        try {
            // add the class column
            $dbconn->begin();
            $query = "ALTER TABLE $table ADD COLUMN class TINYINT";              
            $dbconn->Execute($query);
    
            // get themes from db 
            $query = "SELECT themes.regid,
                             themes.directory
                      FROM $table AS themes";
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery(array());
            // update theme classes        
            while($result->next()) {
                list($regid,$directory) = $result->fields;
                $info = xarTheme_getFileInfo($directory);
                if (!$info) continue; // skip themes missing a xartheme.php 
                $query = "UPDATE $table
                          SET class = ? WHERE regid = ?";
                $bindvars = array($info['class'], $regid);
                $stmt = $dbconn->prepareStatement($query);
                $stmt->executeQuery($bindvars);
            }
            $result->close();
            
            $dbconn->commit();     
        } catch (Exception $e) { throw($e);
            // Damn
            $dbconn->rollback();
            $this->fail();
        }
        return $this->success;
    }
}
?>