<?php
class SubItemsProperty extends DataProperty
{
    public $id           = 30069;
    public $name         = 'subitems';
    public $desc         = 'SubItems';
    public $reqmodules   = array('dynamicdata');

    public $include_reference = 1; // tells the object this property belongs to whether to add a reference of itself to me
    // Configuration parameters
    public $initialization_refobject     = 'objects';
    public $titlefield   = '';
    public $where        = '';        // TODO
    public $display      = 1;         // TODO

    public $objectref      = null;
    public $subitemsobject = null;
    public $itemsdata         = array();                   // holds the subitem objects
    public $toupdate          = array();                   // holds the ids of items to update
    public $tocreate          = array();                   // holds the ids of items to cerate
    public $todelete          = array();                   // holds the ids of items to delete

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'dynamicdata';
        $this->filepath   = 'modules/dynamicdata/xarproperties';

        $this->fieldprefix    = 'dd_'.$this->id;
    }

    public function checkInput($name = '', $value = null)
    {
        // Get the list of item ids, both current and previous
        if(!xarVarFetch('subitem_ids',         'str',   $itemids,       '', XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('subitem_previous_ids','str',   $previous_itemids,       '', XARVAR_DONT_SET)) {return;}
        $itemids = empty($itemids) ? array() : explode(',',$itemids);
        $previous_itemids = empty($previous_itemids) ? array() :explode(',',$previous_itemids);

        // Calculate what rows require what actions
        $this->toupdate = array_intersect($itemids,$previous_itemids);
        $this->tocreate = array_diff($itemids,$previous_itemids);
        $this->todelete = array_diff($previous_itemids,$itemids);

        // Get the object we'll be working with
        if (empty($this->objectref)) throw new Exception(xarML('A subitem property must be part of an pbject'));
        $data['object'] = DataObjectMaster::getObject(array('name'  => $this->initialization_refobject));
        
        // Get this propery's name
        $name = empty($name) ? 'dd_'.$this->id : $name;
        
        // First we need to check all the data on the template
        // If checkInput fails, don't bail
        $itemsdata = array();
        $isvalid = true;
        // We won't check all the items, just those that are to be created or updated
        $itemids = array_merge($this->tocreate,$this->toupdate);
        foreach ($itemids as $prefix) {
            $data['object']->setFieldPrefix($prefix . "_" . $name);
            $thisvalid = $data['object']->checkInput();
            $isvalid = $isvalid && $thisvalid;
        // Store each item for later processing
            $this->itemsdata[$prefix] = $data['object']->getFieldValues();
        }
        return $isvalid;
    }

    public function createValue($itemid=0)
    {
        // Get the link properties of both the parent and the subobject for use in creates and deletes
        $objectarray = unserialize($this->objectref->objects);
        foreach ($objectarray as $key => $value){
            $valueparts = explode('.',$value);
            if ($valueparts[0] == $this->initialization_refobject) {
                $sublink = $valueparts[1];
                $keyparts = explode('.',$key);
                $link = $keyparts[1];
                break;
            }
        }
        
        // Create or update each item
        $this->subitemsobject = DataObjectMaster::getObject(array('name' => $this->initialization_refobject));
        foreach ($this->itemsdata as $itemid => $itemdata) {
            $this->subitemsobject->setFieldValues($itemdata);
            if (in_array($itemid, $this->tocreate)) {
                // Insert the link value
                $this->subitemsobject->properties[$sublink]->value = $this->objectref->properties[$link]->value;
                $item = $this->subitemsobject->createItem();
            } elseif (in_array($itemid, $this->toupdate)) {
                $itemid = $this->subitemsobject->updateItem();
            }
        // Clear the itemid property in preparation for the next round
            unset($this->subitemsobject->itemid);
        }

        // Delete any items that are no longer present
        $this->deleteValue($itemid);
        
        return true;
    }

    public function updateValue($itemid=0)
    {
        return $this->createValue($itemid);
    }

    public function deleteValue($itemid=0)
    {
        $this->subitemsobject = DataObjectMaster::getObject(array('name' => $this->initialization_refobject));
        foreach($this->todelete as $id)
            $this->subitemsobject->deleteItem(array('itemid' => $id));
        return $itemid;
    }

    public function showInput(Array $data = array())
    {
        if (!isset($data['name'])) $data['name'] = 'dd_'.$this->id;
        if (!isset($data['label'])) $data['label'] = $this->label;

        if (!empty($data['object']))  $this->initialization_refobject = $data['object'];
        if (empty($data['fieldprefix'])) $data['fieldprefix'] = $data['name'];

        // This will hold the item(s)
        $data['object'] = DataObjectMaster::getObject(array('name'  => $this->initialization_refobject));
        $data['defaultfieldvalues'] = $data['object']->getFieldValues();

        // Check for the items data:
        // 1. Override from the tag
        // 2. The property's itemsdata array (means checkInput ran)
        // 3. The parent object's items array (means we are getting the data from db)
        if (empty($data['items'])) {
            if (!empty($this->itemsdata)) $data['items'] = $this->itemsdata;
            else $data['items'] = $this->transposeItems();
        }
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        // If there is no override from the tag, rearrange the items
        if (empty($data['items'])) {
            $data['items'] = $this->transposeItems();
        }
        $data['object'] = DataObjectMaster::getObjectList(array('name'  => $this->initialization_refobject));
        $data['object']->items =& $data['items'];
        return parent::showOutput($data);
    }

    private function transposeItems()
    {
        if (empty($this->objectref->items)) return array();
        $itemsarray = current($this->objectref->items);
        $namelength = strlen($this->initialization_refobject) + 1;
        foreach ($itemsarray as $key => $value) {
            $cleankey = substr($key, $namelength);
            foreach ($value as $key1 => $value1) {
                $items[$key1][$cleankey] = $value1;
            }
        }
        return $items;
    }
}
?>