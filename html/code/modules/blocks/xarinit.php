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
function blocks_init()
{
    // Get database information
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();
    $prefix = xarDB::getPrefix();

    // Create tables inside a transaction
    try {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();

        sys::import('xaraya.installer');
        Installer::createTable('schema', 'blocks');
        $idref_type    = array('type'=>'integer', 'unsigned'=>true, 'null'=>false);
        $template_type = array('type'=>'varchar', 'size'=>254, 'null'=>true, 'default'=>null, 'charset' => $charset);

        // *_userblocks
        /* Removed Collapsing blocks to see if there is a better solution.
         $query = xarDBCreateTable($prefix . '_userblocks',
         array('id'         => array('type'    => 'integer',
         'null'    => false,
         'default' => '0'),
         'bid'         => array('type'    => 'varchar',
         'size'    => 32,
         'null'    => false,
         'default' => '0'),
         'active'      => array('type'    => 'integer',
         'size'    => 'tiny',
         'null'    => false,
         'default' => '1'),
         'last_update' => array('type'    => 'timestamp',
         'null'    => false)));

         $result = $dbconn->Execute($query);

         $query = xarDBCreateIndex($prefix . '_userblocks',
         array('name'   => $prefix . '_userblocks',
         'fields' => array('id', 'bid'),
         'unique' => true));
         $result = $dbconn->Execute($query);



         // Register BL tags
         sys::import('blocklayout.template.tags');
         xarTplRegisterTag('blocks', 'blocks-stateicon',
         array(new xarTemplateAttribute('bid', XAR_TPL_STRING|XAR_TPL_REQUIRED)),
         'blocks_userapi_handleStateIconTag');
        */
        /* these can't be set because they are part of the core
         and when the core is installed, blocks is installed
         before the modules module is so, the module_vars table
         isn't even created at this point.

         xarModVars::set('blocks','collapseable',1);
         xarModVars::set('blocks','blocksuparrow','upb.gif');
         xarModVars::set('blocks','blocksdownarrow','downb.gif');
        */
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    // Initialisation successful
    xarModVars::set('blocks', 'selstyle', 'plain');
    xarModVars::set('blocks', 'noexceptions', 1);

    /* There are old block instances defined previously in privs xarsetup.php file and used in the Block module.
       From this version we are adding management of security for blocks to Blocks module
       Old functionality in modules still exists.
       Note that the old instances and masks and code in the files was not 'matched' so don't think they worked properly in any case.
    */
    xarRemoveInstances('blocks');
    //setup the new ones
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $prefix = xarDB::getPrefix();

    $blockGroupsTable    = $prefix . '_block_groups';
    $blockTypesTable     = $prefix . '_block_types';
    $blockInstancesTable = $prefix . '_block_instances';

    //The block instances differ and now defined on name (not title)
    //These need to be upgraded
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
    return blocks_upgrade('2.3.0');
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
        case '2.3.0':
            // Register blocks module event observers 
            xarEvents::registerObserver('ModRemove', 'blocks');            
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
