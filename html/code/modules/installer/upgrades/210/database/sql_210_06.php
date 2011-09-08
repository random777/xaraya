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

class sql_210_06 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Removing the 'DenyBlocks' privilege
                        ");
    }

    public function run()
    {    
        // Define parameters
        $privileges = xarDB::getPrefix() . '_privileges';
        $privmembers = xarDB::getPrefix() . '_privmembers';
    
        // Run the query
        $dbconn = xarDB::getConn();
        try {
            $dbconn->begin();
            $data['sql'] = "
            DELETE p, pm FROM $privileges p INNER JOIN $privmembers pm WHERE p.id = pm.privilege_id AND p.name = 'DenyBlocks' AND p.itemtype= 2;
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