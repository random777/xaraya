<?php
/**
 * Dynamic data initialization
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */
sys::import('xaraya.tableddl');
/**
 * Initialise the dynamicdata module
 *
 * This function is only ever called once during the lifetime of a particular
 * module instance
 * @author mikespub <mikespub@xaraya.com>
 */
function dynamicdata_init()
{
    /**
     * Create tables
     */
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $prefix = xarDB::getPrefix();

    $dynamic_objects = $xartable['dynamic_objects'];
    $dynamic_properties = $xartable['dynamic_properties'];
    $dynamic_data = $xartable['dynamic_data'];
    $dynamic_configurations = $xartable['dynamic_configurations'];
    $dynamic_properties_def = $xartable['dynamic_properties_def'];
    $modulestable = $xartable['modules'];

    // Create tables inside a transaction
    try {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();
        sys::import('xaraya.installer');
        Installer::createTable('schema', 'dynamicdata');

# --------------------------------------------------------
#
# Create the object and property dataobjects
#

        $module_id = xarMod::getRegID('dynamicdata');

        // create default objects for dynamic data
        $sql = "INSERT INTO $dynamic_objects (
                name, label,
                module_id, itemtype, class, filepath, urlparam,
                maxid, config, isalias)
                VALUES (?,?,?,?,?,?,?,?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        $objects = array(
            array('objects'   ,'Dynamic Objects'   ,$module_id,0,'DataObject','auto', 'itemid',0,'a:3:{s:14:"display_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"200";s:7:"failure";s:1:"0";}s:13:"modify_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"800";s:7:"failure";s:1:"0";}s:13:"delete_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"800";s:7:"failure";s:1:"0";}}' ,false),
            array('properties','Dynamic Properties',$module_id,1,'DataObject','auto', 'itemid',0,'a:3:{s:14:"display_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"200";s:7:"failure";s:1:"0";}s:13:"modify_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"800";s:7:"failure";s:1:"0";}s:13:"delete_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"800";s:7:"failure";s:1:"0";}}' ,false),
        );

        $objectid = array();
        $idx = 0;
        foreach ($objects as &$object) {
            $stmt->executeUpdate($object);
            $idx++;
            $objectid[$idx] = $dbconn->getLastId($dynamic_objects);
        }


# --------------------------------------------------------
#
        // create default properties for dynamic data objects
        $sql = "INSERT INTO $dynamic_properties (
                name, label, object_id,
                type, defaultvalue, source,
                status, seq, configuration)
            VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        // TEMP FIX for the constants, rewrite this
        sys::import('modules.dynamicdata.class.properties');
        $properties = array(
            // Properties for the Objects DD object
            array('objectid'  ,'Id'                 ,$objectid[1],21,''            ,$dynamic_objects.'.id'         ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_NOINPUT,1 ,''),
            array('name'      ,'Name'               ,$objectid[1],2 ,''            ,$dynamic_objects.'.name'       ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,2 ,''),
            array('label'     ,'Label'              ,$objectid[1],2 ,''            ,$dynamic_objects.'.label'      ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,3 ,''),
            array('module_id' ,'Module'             ,$objectid[1],19,'182'         ,$dynamic_objects.'.module_id'  ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_NOINPUT,5 ,'a:4:{s:14:"display_layout";s:7:"default";s:24:"initialization_refobject";s:7:"modules";s:25:"initialization_store_prop";s:2:"id";s:27:"initialization_display_prop";s:4:"name";}'),
            array('itemtype'  ,'Item Type'          ,$objectid[1],20,''            ,$dynamic_objects.'.itemtype'   ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_NOINPUT,6 ,'a:5:{s:18:"display_combo_mode";s:1:"2";s:14:"display_layout";s:7:"default";s:19:"validation_override";s:1:"1";s:21:"initialization_module";s:3:"182";s:23:"initialization_itemtype";s:1:"0";}'),
            array('class'     ,'Class'              ,$objectid[1],2 ,'DataObject'  ,$dynamic_objects.'.class'      ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,7 ,''),
            array('filepath'  ,'Location'           ,$objectid[1],2 ,'auto'        ,$dynamic_objects.'.filepath'   ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,8 ,''),
            array('urlparam'  ,'URL Param'          ,$objectid[1],2 ,'itemid'      ,$dynamic_objects.'.urlparam'   ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,9 ,''),
            array('maxid'     ,'Max Id'             ,$objectid[1],15,'0'           ,$dynamic_objects.'.maxid'      ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,10 ,''),
            array('config'    ,'Configuration'      ,$objectid[1],999 ,''          ,$dynamic_objects.'.config'     ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,11 ,'a:7:{s:15:"display_columns";s:2:"30";s:12:"display_rows";s:1:"1";s:17:"display_key_label";s:3:"Key";s:19:"display_value_label";s:5:"Value";s:14:"display_layout";s:7:"default";s:24:"initialization_addremove";s:1:"2";s:32:"initialization_associative_array";s:1:"1";}'),
            array('isalias'   ,'Alias in short URLs',$objectid[1],14,true          ,$dynamic_objects.'.isalias'    ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,12 ,''),

            // Properties for the Properties DD object
            array('id'        ,'Id'                 ,$objectid[2],21,''            ,$dynamic_properties.'.id'        ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,1 ,''),
            array('name'      ,'Name'               ,$objectid[2],2 ,''            ,$dynamic_properties.'.name'      ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,2 ,''),
            array('label'     ,'Label'              ,$objectid[2],2 ,''            ,$dynamic_properties.'.label'     ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,3 ,''),
            array('objectid'  ,'Object'             ,$objectid[2],24,''            ,$dynamic_properties.'.object_id' ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,4 ,''),
            array('type'      ,'Property Type'      ,$objectid[2],22,''            ,$dynamic_properties.'.type'      ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,7 ,''),
            array('defaultvalue' ,'Default Value'   ,$objectid[2],3 ,''            ,$dynamic_properties.'.defaultvalue'   ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,8 ,''),
            array('source'    ,'Source'             ,$objectid[2],23,'dynamic_data',$dynamic_properties.'.source'    ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE,9 ,''),
            array('status'    ,'Status'             ,$objectid[2],25,'1'           ,$dynamic_properties.'.status'    ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,10,''),
            array('seq'       ,'Order'              ,$objectid[2],15,'0'           ,$dynamic_properties.'.seq'       ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,11,''),
            array('configuration','Configuration'   ,$objectid[2],3 ,''            ,$dynamic_properties.'.configuration',DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,12,''),
        );

        $propid = array();
        $idx = 0;
        foreach ($properties as &$property) {
            $stmt->executeUpdate($property);
            $idx++;
            $propid[$idx] = $dbconn->getLastId($dynamic_properties);
        }

        /**
         * Configurations table
         */

        $configfields = array(
            'id'   => array(
                'type'        => 'integer',
                'unsigned'     => true,
                'null'        => false,
                'default'     => '0',
                'increment'   => true,
                'primary_key' => true
            ),
            'name'      => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'default'     => '',
                'charset'     => $charset,
            ),
            'description'     => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'default'     => '',
                'charset'     => $charset,
            ),
            'property_id'     => array(
                'type'        => 'integer',
                'unsigned'     => true,
                'null'        => false,
                'default'     => '0'
            ),
            'label'     => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'default'     => '',
                'charset'     => $charset,
            ),
            'ignore_empty'     => array(
                'type'        => 'boolean',
                'default'     => false
            ),
            'configuration'   => array(
                'type'        => 'text',
                'size'        => 'medium',
                'null'        => 'false',
                'charset'     => $charset,
            )
        );
        $query = xarDBCreateTable($dynamic_configurations,$configfields);
        $dbconn->Execute($query);


        $dbconn->commit();
    } catch (Exception $e) {
        // nice try
        $dbconn->rollback();
        throw $e;
    }

# --------------------------------------------------------
#
# Set up modvars
#
    xarModVars::set('dynamicdata', 'items_per_page', 20);
// CHECKME: syntax error for short urls ?
    xarModVars::set('dynamicdata', 'shorturla', 0);
    xarModVars::set('dynamicdata', 'use_module_alias',0);
    xarModVars::set('dynamicdata', 'module_alias_name','Query');
    xarModVars::set('dynamicdata', 'debugmode', 0);
    xarModVars::set('dynamicdata', 'debugusers', serialize(array()));
    xarModVars::set('dynamicdata', 'administrators', serialize(array()));
    xarModVars::set('dynamicdata', 'getlinkedobjects', 0);

    /**
     * Register blocks
     */
    xarMod::apiFunc('blocks','admin','register_block_type', array('modName'=>'dynamicdata','blockType'=>'form'));

    /**
     * Register hooks
     */

    // allow searching of content
    xarModRegisterHook('item', 'search', 'GUI', 'dynamicdata', 'user', 'search');
    // when a new module item is being specified
    xarModRegisterHook('item', 'new', 'GUI', 'dynamicdata', 'admin', 'newhook');
    // when a module item is created (uses 'dd_*')
    xarModRegisterHook('item', 'create', 'API', 'dynamicdata', 'admin', 'createhook');
    // when a module item is being modified (uses 'dd_*')
    xarModRegisterHook('item', 'modify', 'GUI', 'dynamicdata', 'admin', 'modifyhook');
    // when a module item is updated (uses 'dd_*')
    xarModRegisterHook('item', 'update', 'API', 'dynamicdata', 'admin', 'updatehook');
    // when a module item is deleted
    xarModRegisterHook('item', 'delete', 'API', 'dynamicdata', 'admin', 'deletehook');
    // when a module configuration is being modified (uses 'dd_*')
    xarModRegisterHook('module', 'modifyconfig', 'GUI', 'dynamicdata', 'admin', 'modifyconfighook');
    // when a module configuration is updated (uses 'dd_*')
    xarModRegisterHook('module', 'updateconfig', 'API', 'dynamicdata', 'admin', 'updateconfighook');
    // when a whole module is removed, e.g. via the modules admin screen
    // (set object ID to the module name !)
    xarModRegisterHook('module', 'remove', 'API', 'dynamicdata', 'admin', 'removehook');

    //  Ideally, people should be able to use the dynamic fields in their
    //  module templates as if they were 'normal' fields -> this means
    //  adapting the get() function in the user API of the module, and/or
    //  using some common data retrieval function (DD) in the future...

    /*  display hook is now disabled by default - use the BL tags or APIs instead
        xarModRegisterHook('item', 'display', 'GUI', 'dynamicdata', 'user', 'displayhook');
    */

    /*********************************************************************
     * Register the module components that are privileges objects
     * Format is
     * register(Name,Realm,Module,Component,Instance,Level,Description)
     *********************************************************************/

    xarRegisterMask('ViewDynamicData','All','dynamicdata','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('EditDynamicData','All','dynamicdata','All','All','ACCESS_EDIT');
    xarRegisterMask('AdminDynamicData','All','dynamicdata','All','All','ACCESS_ADMIN');

    xarRegisterMask('ViewDynamicDataItems','All','dynamicdata','Item','All:All:All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_READ');
    xarRegisterMask('EditDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_EDIT');
    xarRegisterMask('AddDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_ADD');
    xarRegisterMask('DeleteDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_DELETE');
    xarRegisterMask('AdminDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_ADMIN');

    xarRegisterMask('ReadDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_READ');
    xarRegisterMask('EditDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_EDIT');
    xarRegisterMask('AddDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_ADD');
    xarRegisterMask('DeleteDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_DELETE');
    xarRegisterMask('AdminDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_ADMIN');

    xarRegisterMask('ViewDynamicDataBlocks','All','dynamicdata','Block','All:All:All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadDynamicDataBlock','All','dynamicdata','Block','All:All:All','ACCESS_READ');
    /*********************************************************************
     * Define instances for this module
     * Format is
     * setInstance(Module,Component,Query,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
     *********************************************************************/

    $instances = array(
        array(
            'header' => 'external', // this keyword indicates an external "wizard"
            'query'  => xarModURL('dynamicdata', 'admin', 'privileges'),
            'limit'  => 0
        )
    );
    xarDefineInstance('dynamicdata','Item',$instances);

    $instances = array(
        array(
            'header' => 'external', // this keyword indicates an external "wizard"
            'query'  => xarModURL('dynamicdata', 'admin', 'privileges'),
            'limit'  => 0
        )
    );
    xarDefineInstance('dynamicdata','Field',$instances);

    // Installation complete; check for upgrades
    return dynamicdata_upgrade('2.0.0');
}

    /**
 * upgrade the dynamicdata module from an old version
 * This function can be called multiple times
 */
function dynamicdata_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0.0':

            // when a new module item is being specified
            xarModRegisterHook('item', 'new', 'GUI', 'dynamicdata', 'admin', 'newhook');
            // when a module item is created (uses 'dd_*')
            xarModRegisterHook('item', 'create', 'API', 'dynamicdata', 'admin', 'createhook');
            // when a module item is being modified (uses 'dd_*')
            xarModRegisterHook('item', 'modify', 'GUI', 'dynamicdata', 'admin', 'modifyhook');
            // when a module item is updated (uses 'dd_*')
            xarModRegisterHook('item', 'update', 'API', 'dynamicdata', 'admin', 'updatehook');
            // when a module item is deleted
            xarModRegisterHook('item', 'delete', 'API', 'dynamicdata', 'admin', 'deletehook');
            // when a module configuration is being modified (uses 'dd_*')
            xarModRegisterHook('module', 'modifyconfig', 'GUI', 'dynamicdata', 'admin', 'modifyconfighook');
            // when a module configuration is updated (uses 'dd_*')
            xarModRegisterHook('module', 'updateconfig', 'API', 'dynamicdata', 'admin', 'updateconfighook');
            // when a whole module is removed, e.g. via the modules admin screen
            // (set object ID to the module name !)
            xarModRegisterHook('module', 'remove', 'API', 'dynamicdata', 'admin', 'removehook');

        //  Ideally, people should be able to use the dynamic fields in their
        //  module templates as if they were 'normal' fields -> this means
        //  adapting the get() function in the user API of the module, and/or
        //  using some common data retrieval function (DD) in the future...

        /*  display hook is now disabled by default - use the BL tags or APIs instead
            xarModRegisterHook('item', 'display', 'GUI', 'dynamicdata', 'user', 'displayhook');
        */

            // fall through to next upgrade

        case '2.0.1':

            // fall through to next upgrade

        default:
            break;
    }
    return true;
}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @returns bool
 */
function dynamicdata_delete()
{
  //this module cannot be removed
  return false;
}
?>
