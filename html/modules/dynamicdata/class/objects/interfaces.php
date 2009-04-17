<?php
/**
 * Interfaces for dataobjects:
 */

interface iDataObject
{
    public function __construct(DataObjectDescriptor $descriptor);
    public function getItem(Array $data = array());
    public function checkInput(Array $data = array());
    public function showForm(Array $data = array());
    public function showDisplay(Array $data = array());
    public function getFieldValues(Array $data = array(), $bypass = 0);
    public function getDisplayValues(Array $data = array());
    public function createItem(Array $data = array());
    public function updateItem(Array $data = array());
    public function deleteItem(Array $data = array());
    public function getNextItemtype(Array $data = array());
}

interface iDataObjectList
{
    public function __construct(DataObjectDescriptor $descriptor);
    public function setArguments(Array $data = array());
    public function setSort($data);
    public function setWhere($data);
    public function setGroupBy($data);
    public function setCategories($data);
    public function &getItems(Array $data = array());
    public function countItems(Array $data = array());
    public function showView(Array $data = array());
    public function getViewOptions(Array $data = array());
    public function &getViewValues(Array $data = array());
    public function getPager($data = null);
    public function getNext(Array $data = array());
}
?>