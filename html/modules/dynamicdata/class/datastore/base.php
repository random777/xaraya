<?php
/**
 * Base class for Dynamic Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
**/
sys::import('xaraya.datastores.interface');

class DynamicData_Datastore_Base extends DynamicData_Datastore_DDObject implements IBasicDataStore
{
    protected $schemaobject;    // The object representing this datastore as codified by its schema

    public $fields = array();   // array of $name => reference to property in DataObject*
    public $_itemids;           // reference to itemids in DynamicData_Object_List TODO: investigate public scope

    public $cache = 0;

    public $type;

    /**
     * Add a field to get/set in this data store, and its corresponding property
     */
    function addField(DynamicData_Property_Base &$property)
    {
        $name = $this->getFieldName($property);
        if(!isset($name))
            return;

        $this->fields[$name] = &$property; // use reference to original property
    }

    /**
     * Remove all group by fields for this data store (for getItems)
     */
    function cleanGroupBy()
    {
        $this->groupby = array();
    }

    /**
     * Remove all where criteria for this data store (for getItems)
     */
    function cleanWhere()
    {
        $this->where = array();
    }

    /**
     * Remove all sorts for this data store (for getItems)
     */
    function cleanSort()
    {
        $this->sort = array();
    }

    /**
     * Get the field name used to identify this property (by default, the property name itself)
     */
    function getFieldName(DynamicData_Property_Base &$property)
    {
        return $property->name;
    }

    function getItem(array $args = array())
    {
        return $args['itemid'];
    }

    function createItem(array $args = array())
    {
        return $args['itemid'];
    }

    function updateItem(array $args = array())
    {
        return $args['itemid'];
    }

    function deleteItem(array $args = array())
    {
        return $args['itemid'];
    }

    function getItems(array $args = array())
    {
        // abstract?
    }

    function countItems(array $args = array())
    {
        return null; // <-- make this numeric!!
    }
}
?>