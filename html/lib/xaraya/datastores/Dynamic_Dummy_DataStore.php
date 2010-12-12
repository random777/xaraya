<?php
/**
 * Data Store is a dummy (for in-memory data storage, perhaps)
 *
 * @package dynamicdata
 * @subpackage datastores
 */

/**
 * Dummy data store class
 *
 * @package dynamicdata
 */
class Dynamic_Dummy_DataStore extends Dynamic_DataStore
{
    function getItem($args = array())
    {
        $itemid = $args['itemid'];
        foreach (array_keys($this->fields) as $field) {
            $this->fields[$field]->setValue($itemid);
        }
    }

    function getItems($args = array())
    {
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
        } elseif (isset($this->_itemids)) {
            $itemids = $this->_itemids;
        } else {
            $itemids = array();
        }
        foreach ($itemids as $itemid) {
            foreach (array_keys($this->fields) as $field) {
                $this->fields[$field]->setItemValue($itemid, $itemid);
            }
        }
    }

/*
    function createItem($args = array())
    {
        $itemid = $args['itemid'];
        foreach (array_keys($this->fields) as $field) {
            if (method_exists($this->fields[$field],'createvalue')) {
                $this->fields[$field]->createValue($itemid);
            }
        }
    }

    function updateItem($args = array())
    {
        $itemid = $args['itemid'];
        foreach (array_keys($this->fields) as $field) {
            if (method_exists($this->fields[$field],'updatevalue')) {
                $this->fields[$field]->updateValue($itemid);
            }
        }
    }

    function deleteItem($args = array())
    {
        $itemid = $args['itemid'];
        foreach (array_keys($this->fields) as $field) {
            if (method_exists($this->fields[$field],'deletevalue')) {
                $this->fields[$field]->deleteValue($itemid);
            }
        }
    }
*/

}

?>
