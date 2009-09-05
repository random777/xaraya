<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */

/**
 * Factory Class to create Dynamic Data Stores
 *
 * @todo this factory should go into core once we use datastores in more broad ways.
 * @todo the classnames could use a bit of a clean up (shorter, lowercasing)
 */
class DynamicData_DataStore_Master extends Object
{
    /**
     * Class method to get a new dynamic data store (of the right type)
     */
    static function &getDataStore($name = '_dynamic_data_', $type = 'data')
    {
        switch ($type)
        {
            case 'table':
                sys::import('xaraya.datastores.sql.flattable');
                $datastore = new FlatTableDataStore($name);
                break;
            case 'data':
                sys::import('xaraya.datastores.sql.variabletable');
                $datastore = new VariableTableDataStore($name);
                break;
            case 'hook':
                sys::import('xaraya.datastores.hook');
                $datastore = new HookDataStore($name);
                break;
            case 'function':
                sys::import('xaraya.datastores.function');
                $datastore = new FunctionDataStore($name);
                break;
            case 'uservars':
                sys::import('xaraya.datastores.usersettings');
                // TODO: integrate user variable handling with DD
                $datastore = new UserSettingsDataStore($name);
                break;
            case 'modulevars':
                sys::import('xaraya.datastores.sql.modulevariables');
                // TODO: integrate module variable handling with DD
                $datastore = new ModuleVariablesDataStore($name);
                break;

                // TODO: other data stores
            case 'ldap':
                sys::import('xaraya.datastores.ldap');
                $datastore = new LDAPDataStore($name);
                break;
            case 'xml':
                sys::import('xaraya.datastores.file.xml');
                $datastore = new XMLFileDataStore($name);
                break;
            case 'csv':
                sys::import('xaraya.datastores.file.csv');
                $datastore = new CSVFileDataStore($name);
                break;
            case 'dummy':
            default:
                sys::import('xaraya.datastores.dummy');
                $datastore = new DummyDataStore($name);
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
    static function &getDataSources($args = array())
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
        if (!empty($args['table']))
        {
            try
            {
                $meta = xarModAPIFunc('dynamicdata','util','getmeta',$args);
            }
            catch ( NotFoundExceptions $e )
            {
                // No worries
            }
            if (!empty($meta) && !empty($meta[$args['table']]))
            {
                foreach ($meta[$args['table']] as $column)
                    if (!empty($column['source']))
                        $sources[] = $column['source'];
            }
        }

        $dbconn = xarDB::getConn();
        $dbInfo = $dbconn->getDatabaseInfo();
        $dbTables = $dbInfo->getTables();
        foreach($dbTables as $tblInfo)
        {
            $tblColumns = $tblInfo->getColumns();
            foreach($tblColumns as $colInfo)
                $sources[] = $tblInfo->getName().".".$colInfo->getName();
        }
        return $sources;
    }
}
?>
