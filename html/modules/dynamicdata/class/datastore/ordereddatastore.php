<?php

/**
 * Base class for Dynamic Data Stores with a concept of ordering
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
**/

class DynamicData_Datastore_OrderedDataStore extends DynamicData_Datastore_Base implements IOrderedDataStore
{
    public $primary= null;

    public $sort   = array();

    /**
     * Add a field to get/set in this data store, and its corresponding property
     */
    function addField(DynamicData_Property_Base &$property)
    {
        parent::addField($property);
        if(!isset($this->primary) && $property->type == 21)
            // Item ID
            $this->setPrimary($property);
    }

    /**
     * Set the primary key for this data store (only 1 allowed for now)
     */
    function setPrimary(DynamicData_Property_Base &$property)
    {
        $name = $this->getFieldName($property);
        if(!isset($name))
            return;

        $this->primary = $name;
    }

    /**
     * Add a sort criteria for this data store (for getItems)
     */
    function addSort(DynamicData_Property_Base &$property, $sortorder = 'ASC')
    {
        $name = $this->getFieldName($property);
        if(!isset($name))
            return;

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

}
?>
