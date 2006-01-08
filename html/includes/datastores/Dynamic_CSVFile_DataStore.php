<?php
/**
 * Data Store is a CSV file (comma-separated values)
 *
 * @package dynamicdata
 * @subpackage datastores
 */

/**
 * Include the File data store
 *
 */
include_once "includes/datastores/Dynamic_File_DataStore.php";

/**
 * Handle CSV file
 *
 * @package dynamicdata
 */
class Dynamic_CSVFile_DataStore extends Dynamic_File_DataStore
{
    /**
     * Constructor
     */
    function Dynamic_CSVFile_DataStore($name)
    {
        parent::Dynamic_File_DataStore($name);

        $options['delimiter'] = ',';
       
        $this->setOptions($options);
    }
}
?>