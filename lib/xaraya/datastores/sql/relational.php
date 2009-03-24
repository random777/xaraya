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
        $fieldlist = $this->object->getFieldList();
        foreach ($fieldlist as $fieldname) {
            $field = $this->object->properties[$fieldname];
                
            if (empty($field->source)) {
                if (empty($field->initialization_refobject)) continue;
                $this->addqueryfields($q, $field->initialization_refobject);
            } else {
                $q->addfield($field->source . ' AS ' . $field->name);
            }
        }
        $primary = $this->object->properties[$this->object->primary]->source;
        $q->eq($primary, (int)$itemid);

        // Run it
        if (!$q->run()) throw new Exception(xarML('Query failed'));
        $result = $q->output();
        if (empty($result)) return;

        // Set the values of the valid properties
        $index = 0;
        foreach ($result as $row) {
            foreach ($fieldlist as $fieldname) {
                // Subitem properties get special treatment
                if ($this->object->properties[$fieldname]->type == 30069) {
                    $this->setItemValue($itemid, $row, $fieldname);
                } elseif ($index < 1) {
                    $this->setValue($row, $fieldname);
                }
            }
            $index++;
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

        // If no itemid was passed or found on the object, get the next id (or dummy)
        $checkid = false;
        if (empty($itemid)) {
            $itemid = null;
            $checkid = true;
        }
        
        //Make sure we have a primary field
        if (empty($this->object->primary)) throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));

        // Bail if the object has no properties
        if (count($this->object->properties) < 1) return;
        
        $q = clone $this->object->dataquery;
        $q->setType('INSERT');

        $q->clearfields();
        foreach ($this->object->properties as $field) {
            if (empty($field->source)) continue;
            if (isset($args[$field->name])) {
                // We have an override through the method's parameters
                $q->addfield($field->source, $args[$field->name]);
            } elseif ($field->name == $this->object->primary){
                // Ignore the primary value if not set
                if (!isset($itemid)) continue;
                $q->addfield($field->source, $itemid);
            } elseif ($field->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_IGNORED) {
                // Ignore the fields with IGNORE status
                continue;
            } else {
                // No override, just take the value the property already has
                $q->addfield($field->source, $field->value);
            }
        }
        
        // Optimze the query to see if we still have more than 1 table
        $q->optimize();
        
        // Complete the dataquery
        if (count($q->tables) > 1) {
            // Find the primary and pass it to the query so we know which insert to start with
            $q->primary = $this->object->properties[$this->object->primary]->source;
        }

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
            if (empty($field->source)) continue;
            if ($field->name == $this->object->primary) {
                // Ignore the primary value
                continue;
            } elseif ($field->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_IGNORED) {
                // Ignore the fields with IGNORE status
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

        // CHECKME: should an itemid = 0 indicate deleting all items?
        $q->clearconditions();
        $q->eq($this->object->properties[$this->object->primary]->source, $itemid);

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

        // Bail if the object has no properties
        if (count($this->object->properties) < 1) return;
        
        //Make sure we have a primary field
        if (empty($this->object->primary)) throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));

        // Complete the dataquery
        $q = $this->object->dataquery;
        $fieldlist = $this->object->getFieldList();
        foreach ($fieldlist as $fieldname) {
            $field = $this->object->properties[$fieldname];
            
            if (empty($field->source)) {
                if (empty($field->initialization_refobject)) continue;
                $this->addqueryfields($q, $field->initialization_refobject);
            } else {
                $q->addfield($field->source . ' AS ' . $field->name);
            }
        }
        
        // Make sure we include the primary key, even if it won't be displayed
        if (!in_array($this->object->primary, $this->object->fieldlist)) {
            $q->addfield($this->object->properties[$this->object->primary]->source . ' AS ' . $this->object->primary);
        }
        
        // Run the query
        if (!$q->run()) throw new Exception(xarML('Query failed'));
        $result = $q->output();
        if (empty($result)) return;

        // Distribute the results to the appropriate properties

        foreach ($result as $row) {
            // Get the value of the primary key
            $itemid = $row[$this->object->primary];
            
            // add this itemid to the list
            if ($saveids) {
                $this->_itemids[] = $itemid;
            }

            // Set the values of the valid properties
            foreach ($this->object->fieldlist as $fieldname) {
                $this->setItemValue($itemid, $row, $fieldname);
            }
        }    
   }

    /**
     * Assign a query result value to its property in the proper object 
     *
     **/

    private function setValue($value, $field)
    {
    // Is this a subitems property?
        if ($this->object->properties[$field]->type == 30069) {

    // Ignore if we don't have an object
            $subitemsobjectname = $this->object->properties[$field]->initialization_refobject;
            if (empty($subitemsobjectname)) continue;

    // Assign the appropriate value to each of the subitemsobjct's properties
            $subitemsobject = $this->object->properties[$field]->subitemsobject;
            $fieldlist = $subitemsobject->getFieldList();
            foreach ($fieldlist as $subproperty) {
    // If the property is again a subitems property, recall the function
                if ($subitemsobject->properties[$subproperty]->type == 30069) {
                    $this->setValue($value, $field);
                } else {
    // Convert the source field name to this property's name and assign
                   $sourceparts = explode('.',$subitemsobject->properties[$subproperty]->source);
                   $subitemsobject->properties[$subproperty]->setValue($value[$subitemsobjectname . "_" . $sourceparts[1]]);   
                }
             }
        } elseif (empty($this->object->properties[$field]->source)){
    // This is some other property with a virtual datasource, ignore it
        } else {
    // This is not a subitems property: assign the value in the usual way
            $this->object->properties[$field]->value = $value[$this->object->properties[$field]->name];
        }
    }

    private function setItemValue($itemid, $row, $field)
    {
    // Is this a subitems property?
        if ($this->object->properties[$field]->type == 30069) {

    // Ignore if we don't have an object
            $subitemsobjectname = $this->object->properties[$field]->initialization_refobject;
            if (empty($subitemsobjectname)) continue;

    // Assign the appropriate value to each of the subitemsobjct's properties
            $subitemsobject = $this->object->properties[$field]->subitemsobject;
            $fieldlist = $subitemsobject->getFieldList();
            foreach ($fieldlist as $subproperty) {
    // If the property is again a subitems property, recall the function
                if ($subitemsobject->properties[$subproperty]->type == 30069) {
                    $this->setItemValue($itemid, $row, $field);
    // Ignore any other property without a source (for now)
                } elseif (empty($subitemsobject->properties[$subproperty]->source)){
                    continue;
                } else {
    // Convert the source field name to this property's name and assign
    // Note we add the subitems to the rows of the parent object list items array
    // We do this for convenience of calling the items, and because the subobject is defined as an object, not an objectlist 
                    $sourceparts = explode('.',$subitemsobject->properties[$subproperty]->source);
                    $subobjectid = $row[$subitemsobjectname . "_" . $subitemsobject->primary];
                    $this->object->items[$itemid][$subitemsobjectname . "_" . $subproperty][$subobjectid] = $row[$subitemsobjectname . "_" . $sourceparts[1]];
                }
             }
        } elseif (empty($this->object->properties[$field]->source)){
    // This is some other property with a virtual datasource, ignore it
        } else {
    // This is a  property with a normal datasource: assign the value in the usual way
            $this->object->properties[$field]->setItemValue($itemid,$row[$this->object->properties[$field]->name]);
        }
    }
    
    /**
     * Add the properties of a subitems object to the getItems query
     *
     **/
    private function addqueryfields(Query $query, $objectname)
    {
        $object = DataObjectMaster::getObject(array('name' => $objectname));
        foreach ($object->properties as $property) {
            // Ignore fields that are disabled
            if ($property->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED) continue;
            
            if (empty($property->source)) {
                if (empty($property->initialization_refobject)) continue;
                $this->addqueryfields($query, $property->initialization_refobject);
            } else {
                $parts = explode('.', $property->source);
                $query->addfield($object->name . "_" . $property->source . ' AS ' . $object->name . "_" . $parts[1]);
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
        $q = new Query();
        $q->addfield('COUNT(DISTINCT ' . $this->object->properties[$this->object->primary]->source . ')');

        // We need to make sure we only have the primary table here
        $primaryfield = $this->object->properties[$this->object->primary]->source;
        $parts = explode('.',$primaryfield);
        if (!isset($parts[1])) 
            throw new Exception(xarML('Incorrect format for primary field: missing table alias'));            
        $primaryalias = $parts[0];
        $tables = array();
        foreach ($this->object->dataquery->tables as $table)
            if ($table['alias'] == $primaryalias) $tables[] = $table;
        if (empty($tables)) 
            throw new Exception(xarML('Could not identify the primary table'));            
        $q->tables = $tables;
        
        // Run the query
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

        $fieldlist = $this->object->getFieldList();
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
