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

class sql_230_02 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Adding a configuration field to the themes table
                        ");
    }

    public function run()
    {        
        // Define parameters
        $table = xarDB::getPrefix() . '_themes';
    
        // Run the query
        $dbconn  = xarDB::getConn();
        try {
            $dbconn->begin();
            $query = "ALTER TABLE $table ADD COLUMN configuration TEXT";              
            $dbconn->Execute($query);        
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