<?php
/**
 * Base class for SQL Data Stores
 *
 * @package dynamicdata
 * @subpackage datastores
 */

/**
 * Base class for SQL Data Stores
 *
 * @package dynamicdata
 */
class Dynamic_SQL_DataStore extends Dynamic_DataStore
{
    /**
     * Constructor
     */
    function Dynamic_SQL_DataStore($name)
    {
        parent::Dynamic_DataStore($name);

        $options['type'] = xarDBGetType();
        $options['host'] = xarDBGetHost();
        $options['name'] = xarDBGetName();
        $options['table'] = '';
        
        $this->setOptions($options);
    }
    /**
     * Bind a data store
     *
     * @param array $options
     *
     * @return resource $resource
     * @todo raise exception on failure
     * @todo add support for different database connections
     */
    function bind($options = array())
    {
        if (empty($options)) {
            $resource =& xarDBGetConn();
            return $resource;
        }
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
        $return = $resource->Close($resource);
        return $return;
    }
}

?>