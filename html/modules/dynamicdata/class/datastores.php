<?php
/**
 * Utility Class to manage Dynamic Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Utility Class to manage Dynamic Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_DataStore_Master
{
    /**
     * Class method to get a new dynamic data store (of the right type)
     */
    function &getDataStore($name = '_dynamic_data_', $type = 'data')
    {
        switch ($type)
        {
            case 'table':
                require_once "includes/datastores/Dynamic_FlatTable_DataStore.php";
                $datastore = new Dynamic_FlatTable_DataStore($name);
                break;
            case 'data':
                require_once "includes/datastores/Dynamic_VariableTable_DataStore.php";
                $datastore = new Dynamic_VariableTable_DataStore($name);
                break;
            case 'hook':
                require_once "includes/datastores/Dynamic_Hook_DataStore.php";
                $datastore = new Dynamic_Hook_DataStore($name);
                break;
            case 'function':
                require_once "includes/datastores/Dynamic_Function_DataStore.php";
                $datastore = new Dynamic_Function_DataStore($name);
                break;
            case 'uservars':
                require_once "includes/datastores/Dynamic_UserSettings_DataStore.php";
            // TODO: integrate user variable handling with DD
                $datastore = new Dynamic_UserSettings_DataStore($name);
                break;
            case 'modulevars':
                require_once "includes/datastores/Dynamic_ModuleVariables_DataStore.php";
            // TODO: integrate module variable handling with DD
                $datastore = new Dynamic_ModuleVariables_DataStore($name);
                break;

       // TODO: other data stores
            case 'ldap':
                require_once "includes/datastores/Dynamic_LDAP_DataStore.php";
                $datastore = new Dynamic_LDAP_DataStore($name);
                break;
            case 'xml':
                require_once "includes/datastores/Dynamic_XMLFile_DataStore.php";
                $datastore = new Dynamic_XMLFile_DataStore($name);
                break;
            case 'csv':
                require_once "includes/datastores/Dynamic_CSVFile_DataStore.php";
                $datastore = new Dynamic_CSVFile_DataStore($name);
                break;
            case 'dummy':
            default:
                require_once "includes/datastores/Dynamic_Dummy_DataStore.php";
                $datastore = new Dynamic_Dummy_DataStore($name);
                break;
        }
        return $datastore;
    }

    function getDataStores()
    {
    }

    /**
     * Get possible data sources (// TODO: for a module ?)
     *
     * @param $args['table'] optional extra table whose fields you want to add as potential data source
     */
    function &getDataSources($args = array())
    {
        $sources = array();

        // default data source is dynamic data
        $sources[] = 'dynamic_data';

        // module variables
        $sources[] = 'module variables';

        // user settings (= user variables per module)
        $sources[] = 'user settings';

        // session variables // TODO: perhaps someday, if this makes sense
        //$sources[] = 'session variables';

    // TODO: re-evaluate this once we're further along
        // hook modules manage their own data
        $sources[] = 'hook module';

        // user functions manage their own data
        $sources[] = 'user function';

        // no local storage
        $sources[] = 'dummy';

        // try to get the meta table definition
        if (!empty($args['table'])) {
            $meta = xarModAPIFunc('dynamicdata','util','getmeta',$args,0);
            if (!empty($meta) && !empty($meta[$args['table']])) {
                foreach ($meta[$args['table']] as $column) {
                    if (!empty($column['source'])) {
                        $sources[] = $column['source'];
                    }
                }
            }
        }

        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $systemPrefix = xarDBGetSystemTablePrefix();
        $metaTable = $systemPrefix . '_tables';

    // TODO: remove Xaraya system tables from the list of available sources ?
        $query = "SELECT xar_table,
                         xar_field,
                         xar_type,
                         xar_size
                  FROM $metaTable
                  ORDER BY xar_table ASC, xar_field ASC";

        $result =& $dbconn->Execute($query);

        if (!$result) return;

        // add the list of table + field
        while (!$result->EOF) {
            list($table, $field, $type, $size) = $result->fields;
        // TODO: what kind of security checks do we want/need here ?
            //if (xarSecAuthAction(0, 'DynamicData::Field', "$name:$type:$id", ACCESS_READ)) {
            //}
            $sources[] = "$table.$field";
            $result->MoveNext();
        }

        $result->Close();

        return $sources;
    }
}

/**
 * Base class for Dynamic Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_DataStore
{
    var $name;     // some static name, or the table name, or the moduleid + itemtype, or ...
    var $type;
    var $fields;   // array of $name => reference to property in Dynamic_Object*
    var $primary;

    var $sort;
    var $where;
    var $groupby;
    var $join;

    var $_itemids;  // reference to itemids in Dynamic_Object_List

    var $cache = 0;

    function Dynamic_DataStore($name)
    {
        $this->name = $name;
        $this->fields = array();
        $this->primary = null;
        $this->sort = array();
        $this->where = array();
        $this->groupby = array();
        $this->join = array();
    }

    /**
     * Get the field name used to identify this property (by default, the property name itself)
     */
    function getFieldName(&$property)
    {
        return $property->name;
    }

    /**
     * Add a field to get/set in this data store, and its corresponding property
     */
    function addField(&$property)
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

        $this->fields[$name] = &$property; // use reference to original property

        if (!isset($this->primary) && $property->type == 21) { // Item ID
            $this->setPrimary($property);
        }
    }

    /**
     * Set the primary key for this data store (only 1 allowed for now)
     */
    function setPrimary(&$property)
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

        $this->primary = $name;
    }

    function getItem($args)
    {
        return $args['itemid'];
    }

    function createItem($args)
    {
        return $args['itemid'];
    }

    function updateItem($args)
    {
        return $args['itemid'];
    }

    function deleteItem($args)
    {
        return $args['itemid'];
    }

    function getItems($args = array())
    {
    }

    function countItems($args = array())
    {
        return null;
    }

    /**
     * Add a sort criteria for this data store (for getItems)
     */
    function addSort(&$property, $sortorder = 'ASC')
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

        $this->sort[] = array('field'     => $name,
                              'sortorder' => $sortorder);
    }

    /**
     * Remove all sort criteria for this data store (for getItems)
     */
    function cleanSort()
    {
        $this->sort = array();
    }

    /**
     * Add a where clause for this data store (for getItems)
     */
    function addWhere(&$property, $clause, $join, $pre = '', $post = '')
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

        $this->where[] = array('field'  => $name,
                               'clause' => $clause,
                               'join'   => $join,
                               'pre'    => $pre,
                               'post'   => $post);
    }

    /**
     * Remove all where criteria for this data store (for getItems)
     */
    function cleanWhere()
    {
        $this->where = array();
    }

    /**
     * Add a group by field for this data store (for getItems)
     */
    function addGroupBy(&$property)
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

        $this->groupby[] = $name;
    }

    /**
     * Remove all group by fields for this data store (for getItems)
     */
    function cleanGroupBy()
    {
        $this->groupby = array();
    }

    /**
     * Join another database table to this data store (unfinished)
     */
    function addJoin($table, $key, $fields, $where = array(), $andor = 'and', $more = '', $sort = array())
    {
        if (!isset($this->extra)) {
            $this->extra = array();
        }
        $fieldlist = array();
        foreach (array_keys($fields) as $field) {
            $source = $fields[$field]->source;
            // save the source for the query fieldlist
            $fieldlist[] = $source;
            // save the source => property pairs for returning the values
            $this->extra[$source] = & $fields[$field]; // use reference to original property
        }
        $whereclause = '';
        if (is_array($where) && count($where) > 0) {
            foreach ($where as $part) {
// TODO: support pre- and post-parts here too ? (cfr. bug 3090)
                $whereclause .= $part['join'] . ' ' . $part['property']->source . ' ' . $part['clause'] . ' ';
            }
        } elseif (is_string($where)) {
            $whereclause = $where;
        }
        $this->join[] = array('table' => $table,
                              'key' => $key,
                              'fields' => $fieldlist,
                              'where' => $whereclause,
                              'andor' => $andor,
                              'more' => $more);
    }

    /**
     * Remove all join criteria for this data store (for getItems)
     */
    function cleanJoin()
    {
        $this->join = array();
    }

}

?>
