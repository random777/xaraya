<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * (try to) get the "meta" properties of tables via PHP ADODB
 *
 * @author the DynamicData module development team
 * @param $args['table']  optional table you're looking for
 * @returns mixed
 * @return array of field definitions, or null on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_utilapi_getmeta($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($table)) {
        $table = '';
    } elseif (isset($propertybag[$table])) {
        return array($table => $propertybag[$table]);
    }

    $dbconn =& xarDBGetConn();

    // Note: this only works if we use the same database connection
    if (!empty($db) && $db != xarDBGetName()) {
        $dbconn->SelectDB($db);
        $prefix = $db . '.';
    } else {
        $prefix = '';
    }

    if (!empty($table)) {
        $tables = array($table);
    } else {
        $tables = $dbconn->MetaTables();
    }
    if (!isset($tables)) {
        return;
    }

    $metadata = array();
    foreach ($tables as $curtable) {
        $curtable = $prefix . $curtable;
        if (isset($propertybag[$curtable])) {
             $metadata[$curtable] = $propertybag[$curtable];
             continue;
        }

        $fields = $dbconn->MetaColumns($curtable);
        $keys = $dbconn->MetaPrimaryKeys($curtable);

        $id = 1;
        $columns = array();
        foreach ($fields as $field) {
            $fieldname = $field->name;
            $datatype = $field->type;
            $size = $field->max_length;

            // assign some default label for now, by removing the first part (xar_)
            $name = preg_replace('/^.+?_/','',$fieldname);
            $label = strtr($name,'_',' ');
            $label = ucwords($label);
            if (isset($columns[$name])) {
                $i = 1;
                while (isset($columns[$name . '_' . $i])) {
                    $i++;
                }
                $name = $name . '_' . $i;
                $label = $label . '_' . $i;
            }
            $status = 1;

            // assign some default validation for now
            $validation = $datatype;
            $validation .= (empty($size) || $size < 0) ? '' : ' (' . $size . ')';

            // (try to) assign some default property type for now
            // = obviously limited to basic data types in this case
            $dtype = $datatype;
            // skip special definitions (unsigned etc.)
            $dtype = preg_replace('/\(.*$/','',$dtype);
            switch ($dtype) {
                case 'char':
                case 'varchar':
                    $proptype = 2; // Text Box
                    if (!empty($size) && $size > 0) {
                        $validation = "0:$size";
                    }
                    break;
                case 'int':
                case 'integer':
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                    if ($size == 1) {
                        $proptype = 14; // Checkbox
                    } else {
                        $proptype = 15; // Number Box
                    }
                    break;
                case 'float':
                case 'decimal':
                case 'double':
                    $proptype = 17; // Number Box (float)
                    break;
                case 'boolean':
                    $proptype = 14; // Checkbox
                    break;
                case 'date':
                case 'datetime':
                case 'timestamp':
                    $proptype = 8; // Calendar
                    break;
                case 'text':
                    $proptype = 4; // Medium Text Area
                    $status = 2;
                    break;
                case 'longtext':
                    $proptype = 5; // Large Text Area
                    $status = 2;
                    break;
                case 'blob':       // caution, could be binary too !
                    $proptype = 4; // Medium Text Area
                    $status = 2;
                    break;
                case 'enum':
                    $proptype = 6; // Dropdown
                    $validation = strtr($validation,array('enum(' => '', ')' => '', "'" => '', ',' => ';'));
                    break;
                default:
                    $proptype = 1; // Static Text
                    break;
            }

            // try to figure out if it's the item id
            if (!empty($keys) && in_array($fieldname,$keys)) {
                // not allowed to modify primary key !
                $proptype = 21; // Item ID
            }

            $columns[$name] = array('name' => $name,
                                   'label' => $label,
                                   'type' => $proptype,
                                   'id' => $id,
                                   'default' => '', // unknown here
                                   'source' => $curtable . '.' . $fieldname,
                                   'status' => $status,
                                   'order' => $id,
                                   'validation' => $validation);
            $id++;
        }
        $metadata[$curtable] = $columns;
        $propertybag[$curtable] = $columns;
    }

    // Note: this only works if we use the same database connection
    if (!empty($db) && $db != xarDBGetName()) {
        $dbconn->SelectDB(xarDBGetName());
    }

    return $metadata;
}

?>