<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author Marc Lutolf <random@xaraya.com>
 */

sys::import('modules.base.xarproperties.dropdown');

/**
 * Handle the module itemtype property
 *
 * A dropdown giving the extensions based on a given module
 * to use for displaying possible parents of an extension
 */
class ModuleItemtypeProperty extends SelectProperty
{
    public $id         = 600;
    public $name       = 'moduleitemtype';
    public $desc       = 'Parent';
    public $reqmodules = array('dynamicdata');

    public $referencemoduleid = 182;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        if (isset($args['modid'])) $this->referencemoduleid = $args['modid'];
    }

    public function validateValue($value = null)
    {
        if (isset($value)) {
            $this->value = $value;
        }
        return true;
    }

    public function showInput(Array $data = array())
    {
        extract($data);
        $args['module'] = 'base';
        $args['template'] = 'dropdown';
        if (isset($modid)) $this->referencemoduleid = $modid;
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);
        if (!empty($modid)) $this->referencemoduleid = $modid;
        $this->options = $this->getOptions();
        if (isset($value)) {
            $this->value = $value;
        }
        if ($this->value < 1000) {
            $types = DataObjectMaster::getModuleItemTypes(array('moduleid' => $this->referencemoduleid));
            // we may still have a loose end in the module: no appropriate parent
            $name = isset($types[$this->value]) ? $types[$this->value]['label'] : xarML('base itemtype');
            $data['option'] = array('id' => $this->referencemoduleid,
                                    'name' => $name);
            if (empty($template)) {
                $template = 'dropdown';
            }
//            return xarTplProperty('base', $template, 'showoutput', $data);
        } else {
        }
        return parent::showOutput($data);
    }

    // Return a list of array(id => value) for the possible options
    function getOptions()
    {
        $this->options = array();
        $types = DataObjectMaster::getModuleItemTypes(array('moduleid' => $this->referencemoduleid));
        if ($types != array()) {
            foreach ($types as $key => $value) $this->options[] = array('id' => $key, 'name' => $value['label']);
        } else {
            $this->options[] = array('id' => 0, 'name' => xarML('no itemtypes defined'));
        }
        return $this->options;
    }
}
?>