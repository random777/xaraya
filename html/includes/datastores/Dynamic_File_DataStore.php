<?php
/**
 * Base class for File Data Stores
 *
 * @package dynamicdata
 * @subpackage datastores
 */

/**
 * Base class for File Data Stores
 *
 * @package dynamicdata
 */
class Dynamic_File_DataStore extends Dynamic_DataStore
{
    /**
     * Constructor
     */
    function Dynamic_File_DataStore($name)
    {
        parent::Dynamic_DataStore($name);

        $options['filepath'] = '';
        $options['filemode'] = 'r';
       
        $this->setOptions($options);
    }
    /**
     * Bind a data store
     *
     * @param array $options
     *
     * @return resource $resource
     * @todo raise exception on failure
     */
    function bind($options = array()) 
    {
        $resource = fopen($options['filepath'], $options['filemode']);
        return $resource;
    }
    
    /**
     * Unbind a data store
     * 
     * @param resource $resource
     * @return bool
     * @todo raise exception on failure
     */
    function unbind($resource)
    {
        $return = fclose($resource);
        return $return;
    }
    
}

?>