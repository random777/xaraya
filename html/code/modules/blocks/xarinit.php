<?php
/**
 * Initialise the blocks module
 *
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * initialise the blocks module
 * @author Jim McDonald
 * @author Paul Rosania
 */
sys::import('xaraya.tableddl');
function blocks_init()
{

    // Get database information
    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();
    $prefix = xarDB::getPrefix();
    $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
    
    $types_table = $tables['block_types'];
    $instances_table = $tables['block_instances'];    

    try {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();
        sys::import('xaraya.installer');
        Installer::createTable('schema', 'blocks');
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    xarModVars::set('blocks', 'selstyle', 'plain');
    xarModVars::set('blocks', 'noexceptions', 1);

    // checkme: <chris/> The following note seems like a 1x thing
    /* There are old block instances defined previously in privs xarsetup.php file and used in the Block module.
       From this version we are adding management of security for blocks to Blocks module
       Old functionality in modules still exists.
       Note that the old instances and masks and code in the files was not 'matched' so don't think they worked properly in any case.
    */
    // checkme: <chris/> at install, surely we have nothing to remove?
    //xarRemoveInstances('blocks');
    //$blockGroupsTable    = $prefix . '_block_groups';
    $blockTypesTable     = $prefix . '_block_types';
    $blockInstancesTable = $prefix . '_block_instances';

    // checkme: are these necessary now we have anon privs per block instance?
    // todo: if we do keep them the query definitions need to be assessed 
    //The block instances differ and now defined on name (not title)
    //These need to be upgraded <chris/> is this upgrade a 1x thing?
    $query1 = "SELECT DISTINCT module_id FROM $blockTypesTable ";
    $query2 = "SELECT DISTINCT instances.name FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id";
    $instances = array(array('header' => 'Module Name:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Name:',
                             'query' => $query2,
                             'limit' => 20));
    xarDefineInstance('blocks','Block',$instances);

    //Define an instance that refers to items that a block contains
    // checkme: items that a block contains? what does that mean? 
    $query1 = "SELECT DISTINCT instances.name FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id";
    $modulesTable = $prefix . '_modules';
    $query2 = "SELECT DISTINCT name FROM $modulesTable ";
    $instances = array(array('header' => 'Block Name:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Module Name:',
                             'query' => $query2,
                             'limit' => 20));
    xarDefineInstance('blocks','BlockItem',$instances);

    xarRegisterMask('EditBlocks','All','blocks','All','All','ACCESS_EDIT');
    xarRegisterMask('AddBlocks','All','blocks','All','All','ACCESS_ADD');
    xarRegisterMask('ManageBlocks','All','blocks','All','All','ACCESS_DELETE');
    xarRegisterMask('AdminBlocks','All','blocks','All','All','ACCESS_ADMIN');

    // Installation complete; check for upgrades
    return blocks_upgrade('2.2.0');

}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @return boolean true on success, false on failure
 */
function blocks_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.2.0':
            // Register blocks module event observers 
            xarEvents::registerObserver('ModRemove', 'blocks');  
            xarEvents::registerObserver('ModActivate', 'blocks');
            xarEvents::registerObserver('ModDeactivate', 'blocks');         
      default:
      break;
    }
    return true;
}

/**
 * Delete this module
 *
 * @return boolean
 */
function blocks_delete()
{
  //this module cannot be removed
  return false;
}

?>