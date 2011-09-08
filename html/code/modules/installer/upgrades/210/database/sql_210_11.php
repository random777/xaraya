<?php
/**
 * Upgrade SQL file
 *
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

class sql_210_11 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Removing old masks from the Blocks module
                        ");
    }

    public function run()
    {    
        // Define parameters
        $privileges = xarDB::getPrefix() . '_privileges';
    
        // Run the query
        $dbconn = xarDB::getConn();
        try {
            $dbconn->begin();
            $data['sql'] = "
            DELETE FROM $privileges WHERE $privileges.`name` = 'ViewBlock';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            DELETE FROM $privileges WHERE $privileges.`name` = 'ReadBlock';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            DELETE FROM $privileges WHERE $privileges.`name` = 'CommentBlock';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            DELETE FROM $privileges WHERE $privileges.`name` = 'ModerateBlock';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            DELETE FROM $privileges WHERE $privileges.`name` = 'EditBlock';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            DELETE FROM $privileges WHERE $privileges.`name` = 'AddBlock';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            DELETE FROM $privileges WHERE $privileges.`name` = 'DeleteBlock';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            DELETE FROM $privileges WHERE $privileges.`name` = 'AdminBlock';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            DELETE FROM $privileges WHERE $privileges.`name` = 'EditBlockGroup';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            DELETE FROM $privileges WHERE $privileges.`name` = 'ReadBlocksBlock';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            DELETE FROM $privileges WHERE $privileges.`name` = 'ViewAuthsystemBlocks';
            ";
            $dbconn->Execute($data['sql']);
            $dbconn->commit();
        } catch (Exception $e) {
            // Damn
            $dbconn->rollback();
            $this->fail();
        }
        return $this->success;
    }
}
?>
