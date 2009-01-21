<?php
/**
 * @package modules
 * @copyright (C) copyright-placeholderetobject
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */

sys::import('xaraya.structures.descriptor');
sys::import('modules.dynamicdata.class.datastores');
sys::import('modules.dynamicdata.class.properties');

/*
 * generate the variables necessary to instantiate a DataObject or DataProperty class
*/
class DataObjectDescriptor extends ObjectDescriptor
{
    function __construct(Array $args=array())
    {
        $args = self::getObjectID($args);
        parent::__construct($args);
    }

    static function getModID(Array $args=array())
    {
        foreach ($args as $key => &$value) {
            if (in_array($key, array('module','modid','module','moduleid'))) {
                if (empty($value)) $value = xarMod::getRegID(xarMod::getName());
                if (is_numeric($value) || is_integer($value)) {
                    $args['moduleid'] = $value;
                } else {
                    $info = xarMod::getInfo(xarMod::getRegID($value));
                    $args['moduleid'] = xarMod::getRegID($value); //$info['systemid']; FIXME
                }
                break;
            }
        }
        // Still not found?
        if (!isset($args['moduleid'])) {
            if (isset($args['fallbackmodule']) && ($args['fallbackmodule'] == 'current')) {
                $args['fallbackmodule'] = xarMod::getName();
            } else {
                $args['fallbackmodule'] = 'dynamicdata';
            }
            $info = xarMod::getInfo(xarMod::getRegID($args['fallbackmodule']));
            $args['moduleid'] = xarMod::getRegID($args['fallbackmodule']); // $info['systemid'];  FIXME change id
        }
        if (!isset($args['itemtype'])) $args['itemtype'] = 0;
        return $args;
    }

    /**
     * Get Object ID
     *
     * @return array all parts necessary to describe a DataObject
     */
    static function getObjectID(Array $args=array())
    {
        $xartable = xarDB::getTables();

        $q = new xarQuery('SELECT',$xartable['dynamic_objects']);
        $q->open();
        if (isset($args['name'])) {
            $q->eq('name',$args['name']);
        } elseif (!empty($args['objectid'])) {
            $q->eq('id',(int)$args['objectid']);
        } else {
            $args = self::getModID($args);
            $q->eq('module_id', $args['moduleid']);
            $q->eq('itemtype', $args['itemtype']);
        }
        if (!$q->run()) return;
        $row = $q->row();
        if ($row == array()) {
            $args['moduleid'] = isset($args['moduleid']) ? $args['moduleid'] : null;
            $args['itemtype'] = isset($args['itemtype']) ? $args['itemtype'] : null;
            $args['objectid'] = isset($args['objectid']) ? $args['objectid'] : null;
            $args['name'] = isset($args['name']) ? $args['name'] : null;
        } else {
            $args['moduleid'] = $row['module_id'];
            $args['itemtype'] = $row['itemtype'];
            $args['objectid'] = $row['id'];
            $args['name'] = $row['name'];
        }
        if (empty($args['tplmodule'])) $args['tplmodule'] = xarMod::getName($args['moduleid']); //FIXME: go to systemid
        if (empty($args['template'])) $args['template'] = $args['name'];
        return $args;

    }
}

class DataObjectMaster extends Object
{
    protected $descriptor  = null;      // descriptor object of this class

    public $objectid    = null;         // system id of the object in this installation
    public $name        = null;         // name of the object
    public $label       = null;         // label as shown on screen

    public $moduleid    = null;
    public $itemtype    = 0;

    public $urlparam    = 'itemid';
    public $maxid       = 0;
    public $config      = 'a:0:{}';       // the configuration parameters for this DD object
    public $configuration;                // the exploded configuration parameters for this DD object
    public $sources     = 'a:0:{}';       // the db source tables of this object
    public $datasources = array();        // the exploded db source tables of this object
    public $relations   = 'a:0:{}';       // the db source table relations of this object
    public $objects     = 'a:0:{}';       // the names of obejcts related to this one
    public $dataquery;                    // the initialization query of this obect
    public $isalias     = 0;

    public $class       = 'DataObject'; // the class name of this DD object
    public $filepath    = 'auto';       // the path to the class of this DD object (can be empty or 'auto' for DataObject)
    public $properties  = array();      // list of properties for the DD object
    public $datastores  = array();      // list of datastores for the DD object
    public $fieldlist   = array();      // array of properties to be displayed
    public $fieldorder  = array();      // displayorder for the properties
    public $fieldprefix = '';           // prefix to use in field names etc.
    public $status      = 65;           // inital status is active and can add/modify
    public $anonymous   = 0;            // if true forces display of names of properties instead of dd_xx designations

    public $layout = 'default';         // optional layout inside the templates
    public $template = '';              // optional sub-template, e.g. user-objectview-[template].xd (defaults to the object name)
    public $tplmodule = 'dynamicdata';  // optional module where the object templates reside (defaults to 'dynamicdata')
    public $urlmodule = '';             // optional module for use in xarModURL() (defaults to the object module)
    public $viewfunc = 'view';          // optional view function for use in xarModURL() (defaults to 'view')

    public $primary = null;             // primary key is item id
    public $secondary = null;           // secondary key could be item type (e.g. for articles)
    public $filter = false;             // set this true to automatically filter by current itemtype on secondary key
    public $upload = false;             // flag indicating if this object has some property that provides file upload

    /**
     * Default constructor to set the object variables, retrieve the dynamic properties
     * and get the corresponding data stores for those properties
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve, or
     *
     * @param $args['fieldlist'] optional list of properties to use, or
     * @param $args['status'] optional status of the properties to use
     * @param $args['allprops'] skip disabled properties by default
     * @todo  This does too much, split it up
    **/

    function toArray(Array $args=array())
    {
        $properties = $this->getPublicProperties();
        foreach ($properties as $key => $value) if (!isset($args[$key])) $args[$key] = $value;
        //FIXME where do we need to define the modname best?
        if (!empty($args['moduleid'])) $args['modname'] = xarModGetNameFromID($args['moduleid']); //FIXME change to systemid
        return $args;
    }

    function loader(DataObjectDescriptor $descriptor)
    {
        $this->descriptor = $descriptor;
        $this->load();

        xarMod::loadDbInfo('dynamicdata','dynamicdata');

        // use the object name as default template override (*-*-[template].x*)
        if(empty($this->template) && !empty($this->name))
            $this->template = $this->name;

        // get the properties defined for this object
       if(count($this->properties) == 0 && isset($this->objectid)) {
            $args = $this->toArray();
            $args['objectref'] =& $this;
            if(!isset($args['allprops']))   //FIXME is this needed??
                $args['allprops'] = null;

            DataPropertyMaster::getProperties($args); // we pass this object along
        }

        // create the list of fields, filtering where necessary
        $this->fieldlist = $this->getFieldList($this->fieldlist,$this->status);

        // Set the configuration parameters
        //FIXME: can we simplify this?
        $args = $descriptor->getArgs();
        try {
            $configargs = unserialize($args['config']);
            foreach ($configargs as $key => $value) $this->{$key} = $value;
            $this->configuration = $configargs;
        } catch (Exception $e) {}

        // set the specific item id (or 0)
        if(isset($args['itemid'])) $this->itemid = $args['itemid'];
        
        // Set up the db tables
        sys::import('modules.query.class.query');
        $this->dataquery = new Query();
        try {
            $this->datasources = unserialize($args['sources']);
            if (!empty($this->datasources)) {
                foreach ($this->datasources as $key => $value) $this->dataquery->addtable($value,$key);
            }
        } catch (Exception $e) {}

        // Set up the db table relations
        try {
            $relationargs = unserialize($args['relations']);
            foreach ($relationargs as $key => $value) $this->dataquery->join($key,$value);
        } catch (Exception $e) {}

        // Set up the relations to related objects
        try {
            $objectargs = unserialize($args['objects']);
            
            foreach ($objectargs as $key => $value)
                $this->dataquery->join($this->propertysource($key),$this->propertysource($value));

        } catch (Exception $e) {
            die('Bad object relation');
        }
// $this->dataquery->qecho();echo "<br />";
        // build the list of relevant data stores where we'll get/set our data
        if(empty($this->datastores) && count($this->properties) > 0)
           $this->getDataStores();

    }

    private function propertysource($sourcestring)
    {
        $parts = explode('.',$sourcestring);
        if (!isset($parts[1])) throw new Exception(xarML('Bad property definition'));
        if ($parts[0] == 'this') {
            return $this->properties[$parts[1]]->source;
        } else {
            $foreignobject = self::getObject(array('name' => $parts[0]));
            $foreignstore = $foreignobject->properties[$parts[1]]->source;
            $foreignparts = explode('.',$foreignstore);
            $foreignconfiguration = $foreignobject->datasources;
            if (!isset($foreignconfiguration[$foreignparts[0]])) throw new Exception(xarML('Bad foreign datasource'));
            $foreigntable = $foreignconfiguration[$foreignparts[0]];
            
            // Add the foreign table to this object's query
            $this->dataquery->addtable($foreigntable,$parts[0] . "_" . $foreignparts[0]);
            return $parts[0] . "_" . $foreignstore;
        }
    }

    private function getFieldList($fieldlist=array(),$status=null)
    {
        $properties = $this->properties;
        $fields = array();
        if(count($fieldlist) != 0) {
            foreach($fieldlist as $field)
                // Ignore those disabled AND those that don't exist
                if(isset($properties[$field]) && ($properties[$field]->getDisplayStatus() != DataPropertyMaster::DD_DISPLAYSTATE_DISABLED))
                    $fields[$properties[$field]->id] = $properties[$field]->name;
        } else {
            if ($status) {
                // we have a status: filter on it
                foreach($properties as $property)
                    if($property->status && $this->status)
                        $fields[$property->id] = $property->name;
            } else {
                // no status filter: return those that are not disabled
                foreach($this->properties as $property)
                    if($property->getDisplayStatus() != DataPropertyMaster::DD_DISPLAYSTATE_DISABLED)
                        $fields[$property->id] = $property->name;
            }
        }
        return $fields;
    }

    /**
     * Add one object to another
     * This is basically adding the properties and datastores from one object to another
     *
     * @todo can we use the type hinting for the parameter?
     * @todo pass $object by ref?
     * @todo stricten the interface, either an object or an id, not both.
    **/
    private function addObject($object=null)
    {
        if(is_numeric($object))
            $object = self::getObject(
                array('objectid' => $object)
            );

        if(!is_object($object))
            throw new EmptyParameterException(array(),'Not a valid object');

        $properties = $object->getProperties();
        foreach($properties as $newproperty)
        {
            // ignore if this property already belongs to the object
            if(isset($this->properties[$newproperty->name])) continue;
            $props = $newproperty->getPublicProperties();
            $this->addProperty($props);
            if (!isset($this->datastores[$newproperty->datastore])) {
                $newstore = $newproperty->getDataStore();
                $this->addDatastore($newstore[0],$newstore[1]);
            }
            $this->datastores[$newproperty->datastore]->addField($this->properties[$props['name']]);
            $this->fieldlist[] = $newproperty->name;
        }
        $this->fieldorder = array_merge(array_keys($properties), $this->fieldorder);
    }

    /**
     * Get the data stores where the dynamic properties of this object are kept
    **/
    function &getDataStores($reset = false)
    {
        // if we already have the datastores
        if (!$reset && isset($this->datastores) && !empty($this->datastores)) {
            return $this->datastores;
        }

        // if we're filtering on property status and there are no properties matching this status
        if (!$reset && !empty($this->status) && count($this->fieldlist) == 0) {
            return $this->datastores;
        }

        // reset field list of datastores if necessary
        if ($reset && count($this->datastores) > 0) {
            foreach(array_keys($this->datastores) as $storename) {
                $this->datastores[$storename]->fields = array();
            }
        }

        // check the fieldlist for valid property names and for operations like COUNT, SUM etc.
        if (!empty($this->fieldlist) && count($this->fieldlist) > 0) {
            $cleanlist = array();
            foreach($this->fieldlist as $name) {
                if (!strstr($name,'(')) {
                        $cleanlist[] = $name;
                } elseif (preg_match('/^(.+)\((.+)\)/',$name,$matches)) {
                    $operation = $matches[1];
                    $field = $matches[2];
                    if(isset($this->properties[$field]))
                    {
                        $this->properties[$field]->operation = $operation;
                        $cleanlist[] = $field;
                        $this->isgrouped = 1;
                    }
                }
            }
            $this->fieldlist = $cleanlist;
        }

        if (!empty($this->datasources)) {
            $this->addDataStore('relational', 'relational');
            $storename = 'relational';
        } else {
            $this->addDataStore('_dynamic_data_', 'data');
            $storename = '_dynamic_data_';
        }

        foreach($this->properties as $name => $property) {
            if(
                !empty($this->fieldlist) and          // if there is a fieldlist
                !in_array($name,$this->fieldlist) and // but the field is not in it,
                $property->type != 21 or                // and we're not on an Item ID property
                ($property->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED)  // or the property is disabled
            )
            {
                // Skip it.
                $this->properties[$name]->datastore = '';
                continue;
            }

            if (empty($this->fieldlist) || in_array($name,$this->fieldlist)) {
                // we add this to the data store fields
                $this->datastores[$storename]->addField($this->properties[$name]); // use reference to original property
            } else {
                // we only pass this along as being the primary field
                $this->datastores[$storename]->setPrimary($this->properties[$name]);
            }
            // keep track of what property holds the primary key (item id)
            if (!isset($this->primary) && $property->type == 21) {
                $this->primary = $name;
            }
            // keep track of what property holds the secondary key (item type)
            if (empty($this->secondary) && $property->type == 20 && !empty($this->filter)) {
                $this->secondary = $name;
            }
        }
        
        return $this->datastores;
    }

    /**
     * Add a data store for this object
     *
     * @param $name the name for the data store
     * @param $type the type of data store
    **/
    function addDataStore($name = '_dynamic_data_', $type='data')
    {
        // get a new data store
        $datastore = DataStoreFactory::getDataStore($name, $type);

        // add it to the list of data stores
        $this->datastores[$datastore->name] =& $datastore;

        // Pass along a reference to this object
        $this->datastores[$datastore->name]->object = $this;

        // for dynamic object lists, put a reference to the $itemids array in the data store
        if(method_exists($this, 'getItems'))
            $this->datastores[$datastore->name]->_itemids =& $this->itemids;
    }

    /**
     * Get the selected dynamic properties for this object
    **/
    function &getProperties($args = array())
    {
        if(empty($args['fieldlist']))
        {
            if(count($this->fieldlist) > 0) {
                $fieldlist = $this->fieldlist;
            } else {
                return $this->properties;
            }
        } else {
            // Accept a list or an array
            if (!is_array($args['fieldlist'])) $args['fieldlist'] = explode(',',$args['fieldlist']);
            $fieldlist = $args['fieldlist'];
        }


        $properties = array();
        if (!empty($args['fieldprefix'])) {
            foreach($fieldlist as $name) {
                if (isset($this->properties[$name])) {
                    // Pass along a field prefix if there is one
                    $this->properties[$name]->_fieldprefix = $args['fieldprefix'];
                    $properties[$name] = &$this->properties[$name];
                    // Pass along the directive of what property name to display
                    if (isset($args['anonymous'])) $this->properties[$name]->anonymous = $args['anonymous'];
                }
            }
        } else {
            foreach($fieldlist as $name) {
                if (isset($this->properties[$name])) {
                    // Pass along a field prefix if there is one
                    $properties[$name] = &$this->properties[$name];
                    // Pass along the directive of what property name to display
                    if (isset($args['anonymous'])) $this->properties[$name]->anonymous = $args['anonymous'];
                }
            }
        }

        return $properties;
    }

    /**
     * Add a property for this object
     *
     * @param $args['name'] the name for the dynamic property (required)
     * @param $args['type'] the type of dynamic property (required)
     * @param $args['label'] the label for the dynamic property
     * @param $args['datastore'] the datastore for the dynamic property
     * @param $args['source'] the source for the dynamic property
     * @param $args['id'] the id for the dynamic property
     *
     * @todo why not keep the scope here and do this:
     *       $this->properties[$args['id']] = new Property($args); (with a reference probably)
    **/
    function addProperty($args)
    {
        // TODO: find some way to have unique IDs across all objects if necessary
        if(!isset($args['id']))
            $args['id'] = count($this->properties) + 1;
        DataPropertyMaster::addProperty($args,$this);
    }

    /**
     * Class method to retrieve information about all DataObjects
     *
     * @return array of object definitions
    **/
    static function &getObjects(Array $args=array())
    {
        extract($args);
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $bindvars = array();
        xarLogMessage("DB: query in getObjects");
        $query = "SELECT id,
                         name,
                         label,
                         module_id,
                         itemtype,
                         urlparam,
                         maxid,
                         config,
                         isalias
                  FROM $dynamicobjects ";
        if(isset($moduleid))
        {
            $query .= "WHERE module_id = ?";
            $bindvars[] = $moduleid;
        }
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);

        $objects = array();
        while ($result->next())
        {
            $info = array();
            // @todo this depends on fetchmode being numeric
            list(
                $info['objectid'], $info['name'],     $info['label'],
                $info['moduleid'], $info['itemtype'],
                $info['urlparam'], $info['maxid'],    $info['config'],
                $info['isalias']
            ) = $result->fields;
            $objects[$info['objectid']] = $info;
        }
//        $result->Close();
        return $objects;
    }

    /**
     * Class method to retrieve information about a Dynamic Object
     *
     * @param $args['objectid'] id of the object you're looking for, OR
     * @param $args['name'] name of the object you're looking for, OR
     * @return array containing the name => value pairs for the object
     * @todo when we had a constructor which was more passive, this could be non-static. (cheap construction is a good rule of thumb)
     * @todo no ref return?
     * @todo when we can turn this into an object method, we dont have to do db inclusion all the time.
    **/
    static function getObjectInfo(Array $args=array())
    {
        if (!isset($args['objectid']) && (!isset($args['name']))) {
           throw new Exception(xarML('Cannot get object information without an objectid or a name'));
        }

        $cacheKey = 'DynamicData.ObjectInfo';
        if(isset($args['objectid']) && xarCore::isCached($cacheKey,$args['objectid'])) {
            return xarCore::getCached($cacheKey,$args['objectid']);
        }
        if(isset($args['name']) && xarCore::isCached($cacheKey,$args['name'])) {
            return xarCore::getCached($cacheKey,$args['name']);
        }

        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $bindvars = array();
        xarLogMessage('DD: query in getObjectInfo');
        $query = "SELECT id,
                         name,
                         label,
                         module_id,
                         itemtype,
                         class,
                         filepath,
                         urlparam,
                         maxid,
                         config,
                         sources,
                         relations,
                         objects,
                         isalias
                  FROM $dynamicobjects ";
        if (isset($args['objectid'])) {
            $query .= " WHERE id = ? ";
            $bindvars[] = (int) $args['objectid'];
        } else {
            $query .= " WHERE name = ? ";
            $bindvars[] = $args['name'];
        }
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if(!$result->first()) return;
        $info = array();
        list(
            $info['objectid'], $info['name'],     $info['label'],
            $info['moduleid'], $info['itemtype'],
            $info['class'], $info['filepath'],
            $info['urlparam'], $info['maxid'],    
            $info['config'],
            $info['sources'],
            $info['relations'],
            $info['objects'],
            $info['isalias']
        ) = $result->fields;
        $result->close();

        xarCore::setCached($cacheKey,$info['objectid'],$info);
        xarCore::setCached($cacheKey,$info['name'],$info);
        return $info;
    }

    /**
     * Class method to retrieve a particular object definition, with sub-classing
     * (= the same as creating a new Dynamic Object with itemid = null)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve + $args['itemtype'] item type of the object to retrieve
     * @param $args['class'] optional classname (e.g. <module>_DataObject)
     * @return object the requested object definition
     * @todo  automatic sub-classing per module (and itemtype) ?
    **/
    static function &getObject(Array $args=array())
    {
        if(!isset($args['itemid'])) $args['itemid'] = null;

        // Complete the info if this is a known object
        $info = self::getObjectInfo($args);

        if ($info != null) $args = array_merge($args,$info);
        else return $info;

        if(!empty($args['filepath']) && ($args['filepath'] != 'auto')) include_once($args['filepath']);
        if (!empty($args['class'])) {
            if(!class_exists($args['class'])) {
                throw new ClassNotFoundException($args['class']);
            }
        } else {
            //CHECKME: remove this later. only here for backward compatibility
            $args['class'] = 'DataObject';
        }
        // here we can use our own classes to retrieve this
        $descriptor = new DataObjectDescriptor($args);

        // Try to get the object from the cache
        if (xarCore::isCached('DDObject', serialize($args))) {
            $object = clone xarCore::getCached('DDObject', MD5(serialize($args)));
        } else {
            $object = new $args['class']($descriptor);
            xarCore::setCached('DDObject', MD5(serialize($args)), clone $object);
        }
        return $object;
    }

    /**
     * Class method to retrieve a particular object list definition, with sub-classing
     * (= the same as creating a new Dynamic Object List)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['class'] optional classname (e.g. <module>_DataObject[_List])
     * @return object the requested object definition
     * @todo   automatic sub-classing per module (and itemtype) ?
     * @todo   get rid of the classname munging, use typing
    **/
    static function &getObjectList(Array $args=array())
    {
        // Complete the info if this is a known object
        $info = self::getObjectInfo($args);
        if ($info != null) $args = array_merge($args,$info);

        sys::import('modules.dynamicdata.class.objects.list');
        $class = 'DataObjectList';
        if(!empty($args['class']))
        {
            if(class_exists($args['class'] . 'List'))
            {
                // this is a generic classname for the object, list and interface
                $classname = $args['class'] . 'List';
            }
            elseif(class_exists($args['class']))
            {
                // this is a specific classname for the list
                $classname = $args['class'];
            }
        }
        $descriptor = new DataObjectDescriptor($args);

        // here we can use our own classes to retrieve this
        $object = new $class($descriptor);
        return $object;
    }

    /**
     * Class method to retrieve a particular object interface definition, with sub-classing
     * (= the same as creating a new Dynamic Object Interface)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['class'] optional classname (e.g. <module>_DataObject[_Interface])
     * @return object the requested object definition
     * @todo  get rid of the classname munging
     * @todo  automatic sub-classing per module (and itemtype) ?
    **/
    static function &getObjectInterface($args)
    {
        sys::import('modules.dynamicdata.class.interface');

        $class = 'DataObjectInterface';
        if(!empty($args['class']))
        {
            if(class_exists($args['class'] . 'Interface'))
            {
                // this is a generic classname for the object, list and interface
                $class = $args['class'] . 'Interface';
            }
            elseif(class_exists($args['class']))
            {
                // this is a specific classname for the interface
                $class = $args['class'];
            }
        }
        // here we can use our own classes to retrieve this
        $object = new $class($args);
        return $object;
    }

    /**
     * Class method to create a new type of Dynamic Object
     *
     * @param $args['objectid'] id of the object you want to create (optional)
     * @param $args['name'] name of the object to create
     * @param $args['label'] label of the object to create
     * @param $args['moduleid'] module id of the object to create
     * @param $args['itemtype'] item type of the object to create
     * @param $args['urlparam'] URL parameter to use for the object items (itemid, exid, aid, ...)
     * @param $args['maxid'] for purely dynamic objects, the current max. itemid (for import only)
     * @param $args['config'] some configuration for the object (free to define and use)
     * @param $args['isalias'] flag to indicate whether the object name is used as alias for short URLs
     * @param $args['class'] optional classname (e.g. <module>_DataObject)
     * @return integer object id of the created item
    **/
    static function createObject(Array $args)
    {
        // TODO: if we extend dobject classes then probably we need to put the class name here
        $object = self::getObject(array('name' => 'objects'));

        // Create specific part
        $descriptor = new DataObjectDescriptor($args);
        $objectid = $object->createItem($descriptor->getArgs());
        $classname = get_class($object);
        xarLogMessage("Creating an object of class " . $classname . ". Objectid: " . $objectid . ", module: " . $args['moduleid'] . ", itemtype: " . $args['itemtype']);
        unset($object);
        return $objectid;
    }

    static function updateObject(Array $args)
    {
        $object = self::getObject(array('name' => 'objects'));

        // Update specific part
        $itemid = $object->getItem(array('itemid' => $args['objectid']));
        if(empty($itemid)) return;
        $itemid = $object->updateItem($args);
        unset($object);
        return $itemid;
    }

    static function deleteObject($args)
    {
        $descriptor = new DataObjectDescriptor($args);
        $args = $descriptor->getArgs();

        // Last stand against wild hooks and other excesses
        if($args['objectid'] < 3)
        {
            $msg = 'You cannot delete the DataObject or DataProperties class';
            throw new BadParameterException(null, $msg);
        }

        // Get an object list for the object itself, so we can delete its items
        $mylist =& self::getObjectList(
            array(
                'objectid' => $args['objectid'],
            )
        );
        if(empty($mylist))
            return;

        // TODO: delete all the (dynamic ?) data for this object

        // delete all the properties for this object
        foreach(array_keys($mylist->properties) as $name)
        {
            $propid = $mylist->properties[$name]->id;
            $propid = DataPropertyMaster::deleteProperty(
                array('itemid' => $propid)
            );
        }
        unset($mylist);

        // delete the Dynamic Objects item corresponding to this object
        $object = self::getObject(array('objectid' => 1));
        $itemid = $object->getItem(array('itemid' => $args['objectid']));
        if(empty($itemid))
            return;
        $result = $object->deleteItem();
        unset($object);
        return $result;
    }

    /**
     * Get a module's itemtypes
     *
     * @param int     args[moduleid]
     * @param string args[module]
     * @param bool   args[native]
     * @param bool   args[extensions]
     * @todo don't use args
     * @todo pick moduleid or module
     * @todo move this into a utils class?
     */
    static function getModuleItemTypes(Array $args)
    {
        extract($args);
        // Argument checks
        if (empty($moduleid) && empty($module)) {
            throw new BadParameterException('moduleid or module');
        }
        if (empty($module)) {
            $info = xarModGetInfo($moduleid);
            $module = $info['name'];
        }

        $native = isset($native) ? $native : true;
        $extensions = isset($extensions) ? $extensions : true;

        $types = array();
        if ($native) {
            // Try to get the itemtypes
            try {
                // @todo create an adaptor class for procedural getitemtypes in modules
                $types = xarModAPIFunc($module,'user','getitemtypes',array());
            } catch ( FunctionNotFoundException $e) {
                // No worries
            }
        }
        if ($extensions) {
            // Get all the objects at once
            $xartable = xarDB::getTables();
            sys::import('modules.roles.class.xarQuery');
            $q = new xarQuery('SELECT',$xartable['dynamic_objects']);
            $q->addfields(array('id AS objectid','label AS objectlabel','module_id AS moduleid','itemtype AS itemtype'));
            $q->eq('module_id',$moduleid);
            if (!$q->run()) return;

            // put in itemtype as key for easier manipulation
            foreach($q->output() as $row)
                $types [$row['itemtype']] = array(
                                            'label' => $row['objectlabel'],
                                            'title' => xarML('View #(1)',$row['objectlabel']),
                                            'url' => xarModURL('dynamicdata','user','view',array('itemtype' => $row['itemtype'])));
        }

        return $types;
    }

    protected function load()
    {
        $this->descriptor->refresh($this);
    }

}
?>
