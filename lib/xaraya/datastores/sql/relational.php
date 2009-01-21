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
        // Get the itemid from the params or from the object definition
        $itemid = isset($args['itemid']) ? $args['itemid'] : $this->object->itemid;
        $checkid = false;
        if (empty($args['itemid'])) {
            // get the next id (or dummy)
            $itemid = null;
            $checkid = true;
        }
        
        //Make sure we have a primary field
        if (empty($this->object->primary)) throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));

        // Bail if the object has no properties
        if (count($this->object->properties) < 1) return;
        
        $q = clone $this->object->dataquery;
        $q->setType('INSERT');

        // Complete the dataquery
        if (count($q->tables)<2) {
            $q->clearfields();
            foreach ($this->object->properties as $field) {
                if (isset($args[$field->name])) {
                    // We have an override through the method's parameters
                    $q->addfield($field->source, $args[$field->name]);
                } elseif ($field->name == $this->object->primary){
                    // Ignore the primary value if not set
                    if (!isset($itemid)) continue;
                    $q->addfield($field->source, $itemid);
                } else {
                    // No override, just take the value the property already has
                    $q->addfield($field->source, $field->value);
                }
            }
        } else {
            // Set aside our tables we'll be working with
            $this->tables = $q->tables;
            // Set aside our links we'll be working with
            $this->tablelinks = $q->tablelinks;
            
            // Fill all the fields
            $q->clearfields();
            foreach ($this->object->properties as $field) {
                if (isset($args[$field->name])) {
                    // We have an override through the method's parameters
                    $q->addfield($field->source, $args[$field->name]);
                } elseif ($field->name == $this->object->primary){
                    // Ignore the primary value if not set
                    if (!isset($itemid)) continue;
                    $q->addfield($field->source, $itemid);
                } else {
                    // No override, just take the value the property already has
                    $q->addfield($field->source, $field->value);
                }
            }
            
            // Set aside our fields we'll be working with
            $this->fields = $q->fields;

            // Find the primary and get its table alias so we know which insert to start with
            $primarysource = $this->object->properties[$this->object->primary]->source;
            $parts = explode('.',$primarysource);
            if (count($parts) != 2) throw new Exception(xarML('Incorrect datasource'));
            $alias = $parts[0];
            
            $this->runinsert($alias,$this->object->primary);

            foreach ($tables as $table) {
                $q = clone $this->object->dataquery;
                $q->setType('INSERT');
                $q->clearfields();
            }
        }
//        $q->qecho();exit;
//$q->present();exit;

        // Run it
        $q->clearconditions();
        if (!$q->run()) throw new Exception(xarML('Query failed'));

        // get the last inserted id
        if ($checkid) {
            $table = array_pop($q->tables);
            $itemid = $q->lastid($table['name'], $this->object->properties[$this->object->primary]->source);
        }
        unset($q);

        $this->object->properties[$this->object->primary]->value = $itemid;
        return $itemid;
    }
    
    function runinsert($alias='',$primary='')
    {
        if (empty($alias)) return true;
        
        // Get the table we are inserting to and remove it from the array of tables
        $tables = array();
        $thistable = '';
        foreach ($this->tables as $table) {
            if ($table['alias'] == $alias) {
                $thistable = $table;
            } else {
                $tables[] = $table;
            }
        }
        $this->tables = $tables;

        // Get the fields we are inserting to and remove them from the array of fields
        $fields = array();
        $thesefields = array();
        foreach ($this->fields as $field) {
            if ($field['table'] == $alias) {
                $thesefields[] = $field;
            } else {
                $fields[] = $field;
            }
        }
        $this->fields = $fields;
        
        // Run the insert for this table
        $q = new Query('INSERT', $thistable['name']);
        $q->fields = $thesefields;
        if (!$q->run()) throw new Exception(xarML('Query failed'));
        
        // Get the row we just inserted
        $q->setType('SELECT');
        $q->clearfields();
        if (!empty($primary)) $q->eq($primary, $q->lastid($thistable['name'],$primary));
        if (!$q->run()) throw new Exception(xarML('Query failed'));
        
        // 
        $tablelinks = array();
        $theselinks = array();
        foreach ($this->tablelinks as $link) {
            $parts = explode('.',$link['field1']);
            if (count($parts) != 2) throw new Exception(xarML('Incorrect datasource'));
            $tablealias = $parts[0];
            if ($tablealias == $alias) {
                $theselinks[] = $link['field2'];
            } else {
                $parts = explode('.',$link['field2']);
                if (count($parts) != 2) throw new Exception(xarML('Incorrect datasource'));
                $tablealias = $parts[0];
                if ($tablealias == $alias) {
                    $theselinks[] = $link['field1'];
                } else {
                    $tablelinks[] = $link;
                }
            }
        }
        $this->tablelinks = $tablelinks;
        
    }
    
    function updateItem(Array $args = array())
    {
        // Get the itemid from the params or from the object definition
        $itemid = isset($args['itemid']) ? $args['itemid'] : $this->object->itemid;

        //Make sure we have a primary field
        if (empty($this->object->primary)) throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));

        // Bail if the object has no properties
        if (count($this->object->properties) < 1) return;
        
        // Complete the dataquery
        $q = clone $this->object->dataquery;
        $q->setType('UPDATE');
        $q->clearfields();
        foreach ($this->object->properties as $field) {
            if ($field->name == $this->object->primary) {
                // Ignore the primary value
                continue;
            } elseif (isset($args[$field->name])) {
                // We have an override through the methods parameters
                $q->addfield($field->source, $args[$field->name]);
            } else {
                // No override, just take the value the property already has
                $q->addfield($field->source, $field->value);
            }
        }

        // Are we overriding the primary?
        if (isset($itemid)) {
            $q->clearconditions();
            $q->eq($this->object->properties[$this->object->primary]->source, $itemid);
        }

        // Run it
        if (!$q->run()) throw new Exception(xarML('Query failed'));
        unset($q);
        
        return $itemid;
    }

    function deleteItem(Array $args = array())
    {
        // Get the itemid from the params or from the object definition
        $itemid = isset($args['itemid']) ? $args['itemid'] : $this->object->itemid;

        //Make sure we have a primary field
        if (empty($this->object->primary)) throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));

        // Complete the dataquery
        $q = $this->object->dataquery;
        $q->setType('DELETE');

        // Are we overriding the primary?
        if (isset($args['itemid'])) {
            $q->clearconditions();
            $q->eq($this->object->properties[$this->object->primary]->source, $itemid);
        }
        // Run it
        if (!$q->run()) throw new Exception(xarML('Query failed'));

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
        foreach ($this->object->properties as $field) {
            if (empty($field->source)) {
                if (empty($field->initialization_refobject)) continue;
                $this->addqueryfields($q, $field->initialization_refobject);
            } else {
                $q->addfield($field->source .  ' AS ' . $field->name);
            }
        }

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
                if (empty($field->source)) continue;
                $this->object->properties[$field]->setItemValue($itemid,$row[$this->object->properties[$field]->name]);
            }
        }        
    }

    function addqueryfields(Query $query, $objectname)
    {
        $object = DataObjectMaster::getObject(array('name' => $objectname));
        foreach ($object->properties as $property) {
            if (empty($property->source)) {
                $this->addqueryfields($query, $property->initialization_refobject);
                if (empty($property->initialization_refobject)) continue;
            } else {
                $query->addfield($object->name . "_" . $property->source);
            }
        }
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

        //Make sure we have a primary field
        if (empty($this->object->primary)) throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));

        // Complete the dataquery
        $q = $this->object->dataquery;
        $q->addfield('COUNT(DISTINCT ' . $this->object->properties[$this->object->primary]->source . ')');

        // Run it
        if (!$q->run()) throw new Exception(xarML('Query failed'));
        $result = $q->row();
        if (empty($result)) return;

        return (int)current($result);
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
