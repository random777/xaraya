<?php
/**
 * Data Store is a series of flat SQL tables (= typical module tables)
 *
 * @package dynamicdata
 * @subpackage datastores
 */

sys::import('xaraya.datastores.sql');

/**
 * Class for relational datastore
 *
 * @package dynamicdata
 */
class RelationalDataStore extends SQLDataStore
{
    function __toString()
    {
        return "relational";
    }

    /**
     * Get the field name used to identify this property (we use the name of the table field here)
     */
    function getFieldName(DataProperty &$property)
    {
        if (!is_object($property)) debug($property); // <-- this throws an exception
        // support [database.]table.field syntax
        if (preg_match('/^(.+)\.(\w+)$/', $property->source, $matches)) {
            $table = $matches[1];
            $field = $matches[2];
            return $field;
        }
    }

    function getItem(Array $args = array())
    {
        // Get the itemid from the params or from the object definition
        $itemid = isset($args['itemid']) ? $args['itemid'] : $this->object->itemid;

        //Make sure we have a primary field
        if (empty($this->object->primary)) throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));

        // Bail if the object has no properties
        if (count($this->object->properties) < 1) return;
        
        // Complete the dataquery
        $q = $this->object->dataquery;
        foreach ($this->object->properties as $field) $q->addfield($field->source .  ' AS ' . $field->name);
        $primary = $this->object->properties[$this->object->primary]->source;
        $q->eq($primary, (int)$itemid);
//$q->qecho();echo "<br />";
        // Run it
        if (!$q->run()) throw new Exception(xarML('Query failed'));
        $result = $q->row();
        if (empty($result)) return;

        // Set the values of the properties
        $fieldlist = array_keys($this->object->properties);
        foreach ($fieldlist as $field) {
            $this->object->properties[$field]->value = $result[$this->object->properties[$field]->name];
        }
        return $itemid;
    }

    /**
     * Create an item in the flat table
     *
     * @return bool true on success, false on failure
     * @throws BadParameterException
     **/
    function createItem(Array $args = array())
    {
        if (count($this->fields) < 1) return;
        $itemid = $args['itemid'];
        $table = $this->name;
        $itemidfield = $this->primary;

        if (empty($itemidfield)) {
            $itemidfield = $this->getPrimary();
            // can't really do much without the item id field at the moment
            if (empty($itemidfield)) {
                return;
            }
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        // TODO: this won't work for objects with several static tables !
        if (empty($itemid)) {
            // get the next id (or dummy)
            $itemid = null;
            $checkid = true;
        } else {
            $checkid = false;
        }
        $this->fields[$itemidfield]->setValue($itemid);

        $query = "INSERT INTO $table ( ";
        $join = '';
        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->value;
            // skip fields where values aren't set
            if (!isset($value)) {
                continue;
            }
            $query .= $join . $field;
            $join = ', ';
        }
        $query .= " ) VALUES ( ";
        $join = '';
        $bindvars = array();
        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->value;
            // skip fields where values aren't set
            if (!isset($value)) {
                continue;
            }
            // TODO: improve this based on static table info
            $query .= $join . " ? ";
            $bindvars[] = $value;
            $join = ', ';
        }
        $query .= " )";
        $stmt = $this->db->prepareStatement($query);
        $result = $stmt->executeUpdate($bindvars);

        // get the last inserted id
        if ($checkid) {
            $itemid = $this->db->getLastId($table);
        }

        if (empty($itemid)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            throw new BadParameterException(array('item id from table '.$table, 'DataFlatTable_DataStore', 'createItem', 'DynamicData'),$msg);
        }
        $this->fields[$itemidfield]->setValue($itemid);
        return $itemid;
    }

    function updateItem(Array $args = array())
    {
        $itemid = $args['itemid'];
        if (count($this->fields) < 1) return $itemid;
        $table = $this->name;
        $itemidfield = $this->primary;

        if (empty($itemidfield)) {
            $itemidfield = $this->getPrimary();
            // can't really do much without the item id field at the moment
            if (empty($itemidfield)) {
                return;
            }
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        $query = "UPDATE $table ";
        $join = 'SET ';
        $bindvars = array();
        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->value;

            // skip fields where values aren't set, and don't update the item id either
            if (!isset($value) || $field == $itemidfield) {
                continue;
            }
            // TODO: improve this based on static table info
            $query .= $join . $field . '=?';
            $bindvars[] = $value;
            $join = ', ';
        }
        $query .= " WHERE $itemidfield=?";
        $bindvars[] = (int)$itemid;
        $stmt = $this->db->prepareStatement($query);
        $stmt->executeUpdate($bindvars);

        return $itemid;
    }

    function deleteItem(Array $args = array())
    {
        $itemid = $args['itemid'];
        $table = $this->name;
        $itemidfield = $this->primary;

        if (empty($itemidfield)) {
            $itemidfield = $this->getPrimary();
            // can't really do much without the item id field at the moment
            if (empty($itemidfield)) {
                return;
            }
        }

        $query = "DELETE FROM $table WHERE $itemidfield = ?";
        $stmt = $this->db->prepareStatement($query);
        $stmt->executeUpdate(array((int)$itemid));
        return $itemid;
    }

    function getItems(Array $args = array())
    {
        if (!empty($args['numitems'])) {
            $numitems = $args['numitems'];
        } else {
            $numitems = 0;
        }
        if (!empty($args['startnum'])) {
            $startnum = $args['startnum'];
        } else {
            $startnum = 1;
        }
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
        } elseif (isset($this->_itemids)) {
            $itemids = $this->_itemids;
        } else {
            $itemids = array();
        }
        // check if it's set here - could be 0 (= empty) too
        if (isset($args['cache'])) {
            $this->cache = $args['cache'];
        }
        
        $isgrouped = 0;
        if (count($this->groupby) > 0) {
            $isgrouped = 1;
        }
        if (count($itemids) == 0 && !$isgrouped) {
            $saveids = 1;
        } else {
            $saveids = 0;
        }

        //Make sure we have a primary field
        if (empty($this->object->primary)) throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));

        // Bail if the object has no properties
        if (count($this->object->properties) < 1) return;
        
        // Complete the dataquery
        $q = $this->object->dataquery;
        foreach ($this->object->properties as $field) $q->addfield($field->source .  ' AS ' . $field->name);

        // Run it
        if (!$q->run()) throw new Exception(xarML('Query failed'));
        $result = $q->output();
        if (empty($result)) return;

        $fieldlist = array_keys($this->object->properties);
        foreach ($result as $row) {

            // Get the value of the primary key
            $itemid = $row[$this->object->primary];
            
            // add this itemid to the list
            if ($saveids) {
                $this->_itemids[] = $itemid;
            }

            // Set the values of the properties
            foreach ($fieldlist as $field) {
                $this->object->properties[$field]->setItemValue($itemid,$row[$this->object->properties[$field]->name]);
            }
        }
        
/*        // check if we're dealing with GROUP BY fields and/or COUNT, SUM etc. operations
        $isgrouped = 0;
        if (count($this->groupby) > 0) {
            $isgrouped = 1;
        }
        $newfields = array();
        foreach ($fieldlist as $field) {
            if (!empty($this->fields[$field]->operation)) {
                $newfields[] = $this->fields[$field]->operation . '(' . $field . ') AS ' . $this->fields[$field]->operation . '_' . $this->fields[$field]->name;
                $isgrouped = 1;
            } else {
                $newfields[] = $field;
            }
        }

        if ($isgrouped) {
            $query = "SELECT " . join(', ', $newfields) . "
                        FROM " . join(', ', $tables) . $more . " ";
        } else {
            // Note: Oracle doesn't like having the same field in a sub-query twice,
            //       so we use an alias for the primary field here
            $query = "SELECT DISTINCT $itemidfield AS ddprimaryid, " . join(', ', $fieldlist) .
                        " FROM " . join(', ', $tables) . $more . " ";
        }

        $next = 'WHERE';

        $bindvars = array();
        if (count($itemids) > 1) {
            $bindmarkers = '?' . str_repeat(',?',count($itemids)-1);
            $query .= " $next $itemidfield IN ($bindmarkers) ";
            foreach ($itemids as $itemid) {
                $bindvars[] = (int) $itemid;
            }
        } elseif (count($itemids) == 1) {
            $query .= " $next $itemidfield = ? ";
            $bindvars[] = (int)$itemids[0];
        } elseif (count($this->where) > 0) {
            $query .= " $next ";
            foreach ($this->where as $whereitem) {
                $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
            }
        }
        if (count($this->join) > 0 && count($where) > 0) {
            $query .= " ) ";
        }

        if (count($this->groupby) > 0) {
            $query .= " GROUP BY " . join(', ', $this->groupby);
        }

        if (count($this->sort) > 0) {
            $query .= " ORDER BY ";
            $join = '';
            foreach ($this->sort as $sortitem) {
                if (empty($this->fields[$sortitem['field']]->operation)) {
                    $query .= $join . $sortitem['field'] . ' ' . $sortitem['sortorder'];
                } else {
                    $query .= $join . $this->fields[$sortitem['field']]->operation . '_' . $this->fields[$sortitem['field']]->name . ' ' . $sortitem['sortorder'];
                }
                $join = ', ';
            }
        } elseif (!$isgrouped) {
            $query .= " ORDER BY ddprimaryid";
        }

        // We got the query, prepare it
        $stmt = $this->db->prepareStatement($query);

        if ($numitems > 0) {
            $stmt->setLimit($numitems);
            $stmt->setOffset($startnum - 1);
        }
        $result = $stmt->executeQuery($bindvars);

        if (count($itemids) == 0 && !$isgrouped) {
            $saveids = 1;
        } else {
            $saveids = 0;
        }
        $itemid = 0;
        while ($result->next()) {
            $values = $result->getRow();
            if ($isgrouped) {
                $itemid++;
            } else {
                $itemid = array_shift($values);
            }
            // oops, something went seriously wrong here...
            if (empty($itemid) || count($values) != count($fieldlist)) {
                continue;
            }

            // add this itemid to the list
            if ($saveids) {
                $this->_itemids[] = $itemid;
            }

            foreach ($fieldlist as $field) {
                // add the item to the value list for this property
                $this->fields[$field]->setItemValue($itemid,array_shift($values));
            }
        }
        $result->close();
*/
    }

    function countItems(Array $args = array())
    {
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
        } elseif (isset($this->_itemids)) {
            $itemids = $this->_itemids;
        } else {
            $itemids = array();
        }
        // check if it's set here - could be 0 (= empty) too
        if (isset($args['cache'])) {
            $this->cache = $args['cache'];
        }

        $table = $this->name;
        $itemidfield = $this->primary;

        // can't really do much without the item id field at the moment
        if (empty($itemidfield)) {
            return;
        }

        if($this->db->databaseType == 'sqlite') {
            $query = "SELECT COUNT(*)
                      FROM (SELECT DISTINCT $itemidfield FROM $table "; // WATCH OUT, STILL UNBALANCED
        } else {
            $query = "SELECT COUNT(DISTINCT $itemidfield)
                    FROM $table ";
        }

        $bindvars = array();
        if (count($itemids) > 1) {
            $bindmarkers = '?' . str_repeat(',?',count($itemids)-1);
            $query .= " WHERE $itemidfield IN ($bindmarkers) ";
            foreach ($itemids as $itemid) {
                $bindvars[] = (int) $itemid;
            }
        } elseif (count($itemids) == 1) {
            $query .= " WHERE $itemidfield = ? ";
            $bindvars[] = (int)$itemids[0];
        } elseif (count($this->where) > 0) {
            $query .= " WHERE ";
            foreach ($this->where as $whereitem) {
                $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
            }
        }

        // TODO: GROUP BY, LEFT JOIN, ... ? -> cfr. relationships
        if($this->db->databaseType == 'sqlite') $query.=")";

        $stmt = $this->db->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result->first()) return;

        $numitems = $result->getInt(1);
        $result->close();

        return $numitems;
    }

    function getNext(Array $args = array())
    {
        static $temp = array();

        $table = $this->name;
        $itemidfield = $this->primary;

        // can't really do much without the item id field at the moment
        if (empty($itemidfield)) return;

        $fieldlist = array_keys($this->fields);
        // Something to do for us?
        if (count($fieldlist) < 1) return;

        if (!isset($temp['result'])) {
            if (!empty($args['numitems'])) {
                $numitems = $args['numitems'];
            } else {
                $numitems = 0;
            }
            if (!empty($args['startnum'])) {
                $startnum = $args['startnum'];
            } else {
                $startnum = 1;
            }
            if (!empty($args['itemids'])) {
                $itemids = $args['itemids'];
            } elseif (isset($this->_itemids)) {
                $itemids = $this->_itemids;
            } else {
                $itemids = array();
            }

            $query = "SELECT $itemidfield, " . join(', ', $fieldlist) . "
                      FROM $table ";

            $bindvars = array();
            if (count($itemids) > 1) {
                $bindmarkers = '?' . str_repeat(',?',count($itemids)-1);
                $query .= " WHERE $itemidfield IN ($bindmarkers) ";
                foreach ($itemids as $itemid) {
                    $bindvars[] = (int) $itemid;
                }
            } elseif (count($itemids) == 1) {
                $query .= " WHERE $itemidfield = ? ";
                $bindvars[] = (int)$itemids[0];
            } elseif (count($this->where) > 0) {
                $query .= " WHERE ";
                foreach ($this->where as $whereitem) {
                    $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
                }
            }

            // TODO: GROUP BY, LEFT JOIN, ... ? -> cfr. relationships

            if (count($this->sort) > 0) {
                $query .= " ORDER BY ";
                $join = '';
                foreach ($this->sort as $sortitem) {
                    $query .= $join . $sortitem['field'] . ' ' . $sortitem['sortorder'];
                    $join = ', ';
                }
            } else {
                $query .= " ORDER BY $itemidfield";
            }
            // We got the query, prepare it
            $stmt = $this->db->prepareStatement($query);

            // Now set additional parameters if we need to
            if ($numitems > 0) {
                $stmt->setLimit($numitems);
                $stmt->setOffset($startnum-1);
            }
            // Execute it
            $result = $stmt->executeQuery($bindvars);
            $temp['result'] =& $result;
        }

        $result =& $temp['result'];

        // Try to fetch the next row
        if (!$result->next()) {
            $result->close();

            $temp['result'] = null;
            return;
        }

        $values = $result->getRow();
        $itemid = array_shift($values);
        // oops, something went seriously wrong here...
        if (empty($itemid) || count($values) != count($this->fields)) {
            $result->close();

            $temp['result'] = null;
            return;
        }

        $this->fields[$itemidfield]->value = $itemid;
        foreach ($fieldlist as $field) {
            // set the value for this property
            $this->fields[$field]->value = array_shift($values);
        }
        return $itemid;
    }

}

?>
