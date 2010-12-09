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
sys::import('datastores.Dynamic_File_DataStore');

/**
 * Handle CSV file
 *
 * @package dynamicdata
 */
class Dynamic_CSVFile_DataStore extends Dynamic_File_DataStore
{
}

?>