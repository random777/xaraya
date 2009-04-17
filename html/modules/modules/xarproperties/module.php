<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage modules
 * @link http://xaraya.com/index.php/release/1.html
 */

sys::import('modules.dynamicdata.xarproperties.objectref');

/**
 * Handle module property
 * @author mikespub
 */
class ModuleProperty extends ObjectRefProperty
{
    public $id         = 19;
    public $name       = 'module';
    public $desc       = 'Module';
    public $reqmodules = array('modules');

    public $filter = array();

    public $initialization_refobject    = 'modules';            // ID of the object we want to reference
    public $initialization_store_prop   = 'systemid';           // Name of the property we want to use for storage
    public $initialization_display_prop = 'displayname';        // Name of the property we want to use for storage

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/modules/xarproperties';
    }

    function showInput(Array $data=array())
    {
        if (!empty($data['filter'])) $this->filter = $data['filter'];
        if (!empty($data['store_prop'])) $this->initialization_store_prop = $data['store_prop'];
        return parent::showInput($data);
    }

    function getOptions()
    {
        $options = $this->getFirstline();
        if (count($this->options) > 0) {
            if (!empty($firstline)) $this->options = array_merge($options,$this->options);
            return $this->options;
        }
        
        $items = xarModAPIFunc('modules', 'admin', 'getlist',array('filter' => $this->filter));
        foreach($items as $item) {
            try {
                $options[] = array('id' => $item[$this->initialization_store_prop], 'name' => $item[$this->initialization_display_prop]);
            } catch(Exception $e) {}
        }
        return $options;
    }
}
?>
