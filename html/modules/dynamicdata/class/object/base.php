<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/27.html
 */

sys::import('xaraya.structures.descriptor');
sys::import('modules.dynamicdata.class.object.interfaces');

/**
 * DataObject Base class
 */
class DynamicData_Object_Base extends DynamicData_Object_Master implements iDataObject
{

    protected $descriptor  = null;      // descriptor object of this class

    public $itemid         = 0;
    public $missingfields  = array();      // reference to fields not found by checkInput

    /**
     * Inherits from DynamicData_Object_Master and sets the requested item id
     *
     * @param $args['itemid'] item id of the object to get
    **/
    public function __construct(DataObjectDescriptor $descriptor)
    {
      // get the object type information from our parent class
        $this->loader($descriptor);

        // Set the configuration parameters
        $args = $descriptor->getArgs();
        try {
            $configargs = unserialize($args['config']);
            foreach ($configargs as $key => $value) $this->{$key} = $value;
            $this->configuration = $configargs;
        } catch (Exception $e) {}

        // set the specific item id (or 0)
        if(isset($args['itemid'])) $this->itemid = $args['itemid'];

        // see if we can access this object, at least in overview
/*        if(!xarSecurityCheck(
            'ViewDynamicDataItems',1,'Item',
            $this->moduleid.':'.$this->itemtype.':'.$this->itemid)
        ) return;
        */
        
        // Get a reference to each property's value
        foreach ($this->properties as $property) {
            $this->configuration['property_' . $property->name] = array('type' => &$property->type, 'value' => &$property->value);
        }
    }

    /**
     * Retrieve the values for this item
    **/
    public function getItem(Array $args = array())
    {
        if(!empty($args['itemid']))
        {
            if($args['itemid'] != $this->itemid)
                // initialise the properties again and refresh the contents of the object configuration
                foreach($this->properties as $property) {
                    $property->value = $property->defaultvalue;
                    $this->configuration['property_' . $property->name] = array('type' => &$property->type, 'value' => &$property->value);
                }

            $this->itemid = $args['itemid'];
        }
        if(empty($this->itemid))
        {
            $msg = 'Invalid item id in method #(1)() for dynamic object [#(2)] #(3)';
            $vars = array('getItem',$this->objectid,$this->name);
            throw new BadParameterException($vars,$msg);
        }

        if (!empty($this->primary) && !empty($this->properties[$this->primary])) {
            $primarystore = $this->properties[$this->primary]->datastore;
        }

        foreach($this->datastores as $name => $datastore) {
            $itemid = $datastore->getItem($this->toArray());
            // only worry about finding something in primary datastore (if any)
            if(empty($itemid) && !empty($primarystore) && $primarystore == $name) {
                return;
            }
        }

        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if(!empty($args['preview'])) $this->checkInput();
        return $this->itemid;
    }

    public function getInvalids(Array $args = array())
    {
        if (!empty($args['fields'])) {
            $fields = $args['fields'];
        } else {
            $fields = !empty($this->fieldlist) ? $this->fieldlist : array_keys($this->properties);
        }
        $invalids = array();
        foreach($fields as $name) {
            if (!empty($this->properties[$name]->invalid))
                $invalids[$name] = $this->properties[$name]->invalid;
        }
        return $invalids;
    }

    public function clearInvalids()
    {
        if (!empty($args['fields'])) {
            $fields = $args['fields'];
        } else {
            $fields = !empty($this->fieldlist) ? $this->fieldlist : array_keys($this->properties);
        }
        foreach($fields as $name) {
            $this->properties[$name]->invalid = '';
        }
        return true;
    }

    /**
     * Check the different input values for this item
     */
    public function checkInput(Array $args = array(), $suppress=0, $priority='dd')
    {
        if(!empty($args['itemid']) && $args['itemid'] != $this->itemid) {
            $this->itemid = $args['itemid'];
            $this->getItem($args);
        }

        // Allow 0 as a fieldprefix
        if(empty($args['fieldprefix']) || (isset($args['fieldprefix']) && $args['fieldprefix'] !== '0')) {
            $args['fieldprefix'] = $this->fieldprefix; 
        } else {
            $this->fieldprefix = $args['fieldprefix'];
        }

        $isvalid = true;
        if (!empty($args['fields'])) {
            $fields = $args['fields'];
        } else {
            $fields = !empty($this->fieldlist) ? $this->fieldlist : array_keys($this->properties);
        }

        $this->missingfields = array();
        foreach($fields as $name) {
            // Ignore disabled or ignored properties
            if(($this->properties[$name]->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_DISABLED)
            || ($this->properties[$name]->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_IGNORED))
                continue;

            // Give the property this object's reference so it can send back info on missing fields
            $this->properties[$name]->objectref =& $this;

            // We need to check both the given name and the dd_ name
            // checking for any transitory name given a property via $args needs to be done at the property level
            $ddname = 'dd_' . $this->properties[$name]->id;
            if (!empty($args['fieldprefix']) || $args['fieldprefix'] === '0') {
                $name1 = $args['fieldprefix'] . "_" .$name;
                $name2 = $args['fieldprefix'] . "_" .$ddname;
            } else {
                $name1 = $name;
                $name2 = $ddname;
            }
            if ($priority == 'dd') {
                $temp = $name1;
                $name1 = $name2;
                $name2 = $temp;
            }
            if(isset($args[$name])) {
                // Name based check
                $passed = $this->properties[$name]->checkInput($name1,$args[$name]);
                if ($passed === null) {
                    array_pop($this->missingfields);
                    $passed = $this->properties[$name]->checkInput($name2,$args[$name]);
                }
            } elseif(isset($args[$ddname])) {
                // No name, check based on field
                $passed = $this->properties[$name]->checkInput($name1,$args[$ddname]);
                if ($passed === null) {
                    array_pop($this->missingfields);
                    $passed = $this->properties[$name]->checkInput($name2,$args[$ddname]);
                }
            } else {
                // Check without values
                $passed = $this->properties[$name]->checkInput($name1);
                if ($passed === null) {
                    array_pop($this->missingfields);
                    $passed = $this->properties[$name]->checkInput($name2);
                }
            }
            if (($passed === null) || ($passed === false)) $isvalid = false;
        }
        if (!empty($this->missingfields) && !$suppress) {
            throw new VariableNotFoundException(array($this->name,implode(', ',$this->missingfields)),'The following fields were not found: #(1): [#(2)]');
        }
        return $isvalid;
    }

    /**
     * Show an input form for this item
     */
    public function showForm(Array $args = array())
    {
        $args = $this->toArray($args);

        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if(!empty($args['preview'])) $this->checkInput();

        // Set all properties based on what is passed in.
        $properties = $this->getProperties($args);

        if (!empty($args['fieldlist']) && !is_array($args['fieldlist'])) {
            $args['fieldlist'] = explode(',',$args['fieldlist']);
            if (!is_array($args['fieldlist'])) throw new Exception('Badly formed fieldlist attribute');
        }
        
        if(count($args['fieldlist']) > 0 || !empty($this->status)) {
            $args['properties'] = array();
            foreach($args['fieldlist'] as $name) {
                if(isset($this->properties[$name])) {
                    if(($this->properties[$name]->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_DISABLED)
                    || ($this->properties[$name]->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_IGNORED)
                    || ($this->properties[$name]->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_VIEWONLY)) continue;
                        $args['properties'][$name] = $this->properties[$name];
                }
            }
        } else {
            $args['properties'] = array();
            foreach ($properties as $property) {
                if(($property->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_DISABLED)
                || ($this->properties[$name]->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_IGNORED)
                || ($this->properties[$name]->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_VIEWONLY)) continue;
                    $args['properties'][$property->name] = $property;
            }

            // Order the fields if this is an extended object
            if (!empty($this->fieldorder)) {
                $tempprops = array();
                foreach ($this->fieldorder as $field)
                    if (isset($args['properties'][$field]))
                        $tempprops[$field] = $args['properties'][$field];
                $args['properties'] = $tempprops;
            }
        }

        // pass some extra template variables for use in BL tags, API calls etc.
        //FIXME: check these
        $args['isprimary'] = !empty($this->primary);
        $args['catid'] = !empty($this->catid) ? $this->catid : null;
        $args['object'] = $this;
        return xarTplObject($args['tplmodule'],$args['template'],'showform',$args);
    }

    /**
     * Show an output display for this item
     */
    public function showDisplay(Array $args = array())
    {
        $args = $this->toArray($args);
        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if(!empty($args['preview'])) $this->checkInput();

        if (!empty($args['fieldlist']) && !is_array($args['fieldlist'])) {
            $args['fieldlist'] = explode(',',$args['fieldlist']);
            if (!is_array($args['fieldlist'])) throw new Exception('Badly formed fieldlist attribute');
        }
        if(count($args['fieldlist']) > 0 || !empty($this->status)) {
            $args['properties'] = array();
            foreach($args['fieldlist'] as $name) {
                if(isset($this->properties[$name])) {
                    if(($this->properties[$name]->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_DISABLED)
                    || ($this->properties[$name]->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_VIEWONLY)
                    || ($this->properties[$name]->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_HIDDEN)) continue;
                    $args['properties'][$name] = $this->properties[$name];
                }
            }
        } else {
            $args['properties'] =& $this->properties;
            // TODO: this is exactly the same as in the display function, consolidate it.
            $totransform = array(); $totransform['transform'] = array();
            foreach($this->properties as $pname => $pobj) {
                if(($property->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_DISABLED)
                || ($this->properties[$name]->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_VIEWONLY)
                || ($property->getDisplayStatus() == DynamicData_Property_Master::DD_DISPLAYSTATE_HIDDEN)) continue;
                // *never* transform an ID
                // TODO: there is probably lots more to skip here.
                if($pobj->type == '21') continue;
                $totransform['transform'][] = $pname;
                $totransform[$pname] = $pobj->value;
            }

            // CHECKME: is $this->tplmodule safe here?
            $transformed = xarModCallHooks(
                'item','transform',$this->itemid,
                $totransform, $this->tplmodule,$this->itemtype
            );

            foreach($this->properties as $property) {
                if(
                    ($property->getDisplayStatus() != DynamicData_Property_Master::DD_DISPLAYSTATE_DISABLED) &&
                    ($property->getDisplayStatus() != DynamicData_Property_Master::DD_DISPLAYSTATE_VIEWONLY) &&
                    ($property->getDisplayStatus() != DynamicData_Property_Master::DD_DISPLAYSTATE_HIDDEN) &&
                    ($property->type != 21) &&
                    isset($transformed[$property->name])
                )
                {
                    // sigh, 5 letters, but so many hours to discover them
                    // anyways, clone the property, so we can safely change it, PHP 5 specific!!
                    $args['properties'][$property->name] = clone $property;
                    $args['properties'][$property->name]->value = $transformed[$property->name];
                }
            }

            // Order the fields if this is an extended object
            if (!empty($this->fieldorder)) {
                $tempprops = array();
                foreach ($this->fieldorder as $field)
                    if (isset($args['properties'][$field]))
                        $tempprops[$field] = $args['properties'][$field];
                $args['properties'] = $tempprops;
            }

        }

        // pass some extra template variables for use in BL tags, API calls etc.
        //FIXME: check these
        $args['isprimary'] = !empty($this->primary);
        $args['catid'] = !empty($this->catid) ? $this->catid : null;
        $args['object'] = $this;
        return xarTplObject($args['tplmodule'],$args['template'],'showdisplay',$args);
    }

    /**
     * Get the names and values of
     */
    public function getFieldValues(Array $args = array(), $bypass = 0)
    {
        $fields = array();
        $properties = $this->getProperties($args);
        if ($bypass) {
            foreach ($properties as $property) {
                $fields[$property->name] = $property->value;
            }
        } else {
            foreach ($properties as $property) {
                $fields[$property->name] = $property->getValue();
            }
        }
        return $fields;
    }

    public function setFieldValues(Array $args = array(), $bypass = 0)
    {
        if ($bypass) {
            foreach ($args as $key => $value)
                if (isset($this->properties[$key])) $this->properties[$key]->value = $value;
        } else {
            foreach ($args as $key => $value)
                if (isset($this->properties[$key]))  $this->properties[$key]->setValue($value);
        }
        return true;
    }

    /**
     * Get the labels and values to include in some output display for this item
     */
    public function getDisplayValues(Array $args = array())
    {
        $displayvalues = array();
        $properties = $this->getProperties($args);
        foreach($properties as $property) {
            $label = xarVarPrepForDisplay($property->label);
            $displayvalues[$label] = $property->showOutput();
        }
        return $displayvalues;

        /* FIXME: the status value isn't being used correctly I think
        if(count($args['fieldlist']) > 0 || !empty($this->status))
        {
            foreach($args['fieldlist'] as $name)
                if(isset($this->properties[$name]))
                {
                    $label = xarVarPrepForDisplay($this->properties[$name]->label);
                    $displayvalues[$label] = $this->properties[$name]->showOutput();
                }
        }
        else
        {
            foreach(array_keys($this->properties) as $name)
            {
                $label = xarVarPrepForDisplay($this->properties[$name]->label);
                $displayvalues[$label] = $this->properties[$name]->showOutput();
            }
        }
        return $displayvalues;
        */
    }

    /**
     * Get and set for field prefixes
     */
    public function getFieldPrefix()
    {
        return $this->fieldprefix;
    }
    public function setFieldPrefix($prefix)
    {
        $this->fieldprefix = $prefix;
        foreach (array_keys($this->properties) as $property)
            $this->properties[$property]->_fieldprefix = $prefix;
        return true;
    }

    public function createItem(Array $args = array())
    {
        if(count($args) > 0) {
            if(isset($args['itemid'])) {
                $this->itemid = $args['itemid'];
            }
            foreach($args as $name => $value) {
                if(isset($this->properties[$name])) {
                    $this->properties[$name]->setValue($value);
                }
            }
        }

        // special case when we try to create a new object handled by dynamicdata
        if(
            $this->objectid == 1 &&
            $this->properties['module_id']->value == xarMod::getRegID('dynamicdata')
            //&& $this->properties['itemtype']->value < 2
        )
        {
            $this->properties['itemtype']->setValue($this->getNextItemtype($args));
        }

        // check that we have a valid item id, or that we can create one if it's set to 0
        if(empty($this->itemid)) {
            if ($this->baseancestor == 0) {
                $this->baseancestor = 1;
            }
            $primaryobject = DynamicData_Object_Master::getObject(array('objectid' => $this->baseancestor));
            // no primary key identified for this object, so we're stuck
            if(!isset($primaryobject->primary)) {
                $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
                $vars = array('primary key', 'DynamicData_Object_Base', 'createItem', 'dynamicdata');
                throw new BadParameterException($vars,$msg);
            } else {
                if ($this->objectid == 1) {
                    $value = 0;
                } else {
                    $value = $primaryobject->properties[$primaryobject->primary]->getValue();
                }

                // we already have an itemid value in the properties
                if(!empty($value)) {
                    $this->itemid = $value;
                } elseif(!empty($primaryobject->properties[$primaryobject->primary]->datastore)) {
                    // we'll let the primary datastore create an itemid for us
                    $primarystore = $primaryobject->properties[$primaryobject->primary]->datastore;
                    // add the primary to the data store fields if necessary
                    if(!empty($this->fieldlist) && !in_array($primaryobject->primary,$this->fieldlist))
                        $this->datastores[$primarystore]->addField($this->properties[$this->primary]); // use reference to original property

                    // Execute any property-specific code first
                    foreach ($this->datastores[$primarystore]->fields as $property) {
                        if (method_exists($property,'createvalue')) {
                            $property->createValue($this->itemid);
                        }
                    }

                    $this->itemid = $this->datastores[$primarystore]->createItem($this->toArray());
                } else {
                    $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
                    $vars = array('primary key datastore', 'Dynamic Object', 'createItem', 'DynamicData');
                    throw new BadParameterException($vars,$msg);
                }
            }
        }
        if(empty($this->itemid)) return;

        $args = $this->getFieldValues();
        $args['itemid'] = $this->itemid;
        foreach(array_keys($this->datastores) as $store) {
            // skip the primary store
            if(isset($primarystore) && $store == $primarystore)
                continue;

            // Execute any property-specific code first
            if ($store != '_dummy_') {
                foreach ($this->datastores[$store]->fields as $property) {
                    if (method_exists($property,'createvalue')) {
                        $property->createValue($this->itemid);
                    }
                }
            }
            
            // Now run the create routine of the this datastore
            $itemid = $this->datastores[$store]->createItem($args);
            if(empty($itemid))
                return;
        }

        // call create hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
        $modinfo = xarModGetInfo($this->moduleid);
        if(
            !empty($this->primary) &&
            ($modinfo['name'] != 'articles') && ($modinfo['name'] != 'roles')
        )
        {
            $item = array();
            foreach(array_keys($this->properties) as $name)
                $item[$name] = $this->properties[$name]->value;

            $item['module'] = $modinfo['name'];
            $item['itemtype'] = $this->itemtype;
            $item['itemid'] = $this->itemid;
            xarModCallHooks('item', 'create', $this->itemid, $item, $modinfo['name']);
        }
        return $this->itemid;
    }

    public function updateItem(Array $args = array())
    {
        if(count($args) > 0) {
            if(!empty($args['itemid']))
                $this->itemid = $args['itemid'];

            foreach($args as $name => $value)
                if(isset($this->properties[$name]))
                    $this->properties[$name]->setValue($value);
        }
        if(empty($this->itemid)) {
            // Try getting the id value from the item ID property if it exists
            foreach($this->properties as $property)
                if ($property->type == 21) $this->itemid = $property->value;
        }
        if(empty($this->itemid)) {
            $msg = 'Invalid item id in method #(1)() for dynamic object [#(2)] #(3)';
            $vars = array('updateItem',$this->objectid,$this->name);
            throw new BadParameterException($vars,$msg);
        }

        $args = $this->getFieldValues();
        $args['itemid'] = $this->itemid;
        foreach(array_keys($this->datastores) as $store)
        {
            // Execute any property-specific code first
            if ($store != '_dummy_') {
                foreach ($this->datastores[$store]->fields as $property) {
                    if (method_exists($property,'updatevalue')) {
                        $property->updateValue($this->itemid);
                    }
                }
            }

            // Now run the update routine of the this datastore
            $itemid = $this->datastores[$store]->updateItem($args);
            if(empty($itemid))
                return;
        }

        // call update hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
        $modinfo = xarModGetInfo($this->moduleid);
        if(
            !empty($this->primary) &&
            ($modinfo['name'] != 'articles') && ($modinfo['name'] != 'roles')
        )
        {
            $item = array();
            foreach(array_keys($this->properties) as $name)
                $item[$name] = $this->properties[$name]->value;

            $item['module'] = $modinfo['name'];
            $item['itemtype'] = $this->itemtype;
            $item['itemid'] = $this->itemid;
            xarModCallHooks('item', 'update', $this->itemid, $item, $modinfo['name']);
        }
        return $this->itemid;
    }

    public function deleteItem(Array $args = array())
    {
        if(!empty($args['itemid']))
            $this->itemid = $args['itemid'];

        if(empty($this->itemid))
        {
            $msg = 'Invalid item id in method #(1)() for dynamic object [#(2)] #(3)';
            $vars = array('deleteItem',$this->objectid,$this->name);
            throw new BadParameterException($vars, $msg);
        }

        // Last stand against wild hooks and other excesses
        if(($this->objectid < 3) && ($this->itemid < 3))
        {
            $msg = 'You cannot delete the DataObject or DataProperties class';
            throw new BadParameterException(null, $msg);
        }

        // delete the item in all the data stores
        $args = $this->getFieldValues();
        $args['itemid'] = $this->itemid;
        foreach(array_keys($this->datastores) as $store)
        {
            $itemid = $this->datastores[$store]->deleteItem($args);
            if(empty($itemid))
                return;
        }

        // call delete hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
        $modinfo = xarModGetInfo($this->moduleid);
        if(
            !empty($this->primary) &&
            ($modinfo['name'] != 'articles') && ($modinfo['name'] != 'roles')
        )
        {
            $item = array();
            foreach(array_keys($this->properties) as $name)
                $item[$name] = $this->properties[$name]->value;

            $item['module'] = $modinfo['name'];
            $item['itemtype'] = $this->itemtype;
            $item['itemid'] = $this->itemid;
            xarModCallHooks('item', 'delete', $this->itemid, $item, $modinfo['name']);
        }
        return $this->itemid;
    }

    /**
     * Get the next available item type (for objects that are assigned to the dynamicdata module)
     *
     * @param $args['moduleid'] module id for the object
     * @return integer value of the next item type
     *
     * @todo this needs to change into something more safe.
     */
    public function getNextItemtype(Array $args = array())
    {
        if(empty($args['moduleid']))
            $args['moduleid'] = $this->moduleid;

        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $query = "SELECT MAX(itemtype) FROM $dynamicobjects  WHERE module_id = ?";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array((int)$args['moduleid']));
        if(!$result->first()) return; // shouldnt we raise?
        $nexttype = $result->getInt(1);

        // Note: this is *not* reliable in "multi-creator" environments
        $nexttype++;
        return $nexttype;
    }
}
?>
