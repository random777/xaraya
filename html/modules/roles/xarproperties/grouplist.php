<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */
/* Include the base class */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle Group list property
 * @author mikespub <mikespub@xaraya.com>
 */
class GroupListProperty extends SelectProperty
{
    public $id         = 45;
    public $name       = 'grouplist';
    public $desc       = 'Group List';
    public $reqmodules = array('roles');

    public $ancestorlist = array();
    public $parentlist   = array();
    public $grouplist    = array();

    /*
    * Options available to group selection
    * ===================================
    * Options take the form:
    *   option-type:option-value;
    * option-types:
    *   ancestor:name[,name] - select only groups who are descendants of the given group(s)
    *   parent:name[,name] - select only groups who are members of the given group(s)
    *   group:name[,name] - select only the given group(s)
    */

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/roles/xarproperties';

        if (count($this->options) == 0) {
            $this->options = $this->getOptions();
        }

    }

    public function getOptions()
    {
        $select_options = array();
        if (!empty($this->ancestorlist)) {
            $select_options['ancestor'] = implode(',', $this->ancestorlist);
        }
        if (!empty($this->parentlist)) {
            $select_options['parent'] = implode(',', $this->parentlist);
        }
        if (!empty($this->grouplist)) {
            $select_options['group'] = implode(',', $this->grouplist);
        }
        // TODO: handle large # of groups too (optional - less urgent than for users)
        $groups = xarModAPIFunc('roles', 'user', 'getallgroups', $select_options);
        $options = array();
        foreach ($groups as $group) {
            $options[] = array('id' => $group['id'], 'name' => $group['name']);
        }
        return $options;
    }

    public function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            // check if this is a valid group id
            $group = xarModAPIFunc('roles','user','get',
                                   array('id' => $value,
                                         'itemtype' => 1)); // we're looking for a group here
            if (!empty($group)) {
                $this->value = $value;
                return true;
            }
        } elseif (empty($value)) {
            $this->value = $value;
            return true;
        }
        $this->invalid = xarML('selection: #(1)', $this->name);
        $this->value = null;
        return false;
    }

    public function parseValidation($validation = '')
    {
        foreach(preg_split('/(?<!\\\);/', $this->validation) as $option) {
            // Semi-colons can be escaped with a '\' prefix.
            $option = str_replace('\;', ';', $option);
            // An option comes in two parts: option-type:option-value
            if (strchr($option, ':')) {
                list($option_type, $option_value) = explode(':', $option, 2);
                if ($option_type == 'ancestor') {
                    $this->ancestorlist = array_merge($this->ancestorlist, explode(',', $option_value));
                }
                if ($option_type == 'parent') {
                    $this->parentlist = array_merge($this->parentlist, explode(',', $option_value));
                }
                if ($option_type == 'group') {
                    $this->grouplist = array_merge($this->grouplist, explode(',', $option_value));
                }
            }
        }
    }

    public function showInput(Array $data = array())
    {
        if (!empty($data['validation']))
            $this->parseValidation($data['validation']);
            $this->options = $this->getOptions();
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;

        if (empty($value)) {
            $group = array();
            $groupname = '';
        } else {
            $group = xarModAPIFunc('roles','user','get',
                                   array('id' => $value,
                                         'itemtype' => ROLES_GROUPTYPE)); // we're looking for a group here
            if (empty($group) || empty($group['name'])) {
                $groupname = '';
            } else {
                $groupname = $group['name'];
            }
        }
        $data['value']=$value;
        $data['group']=$group;
        $data['groupname']=xarVarPrepForDisplay($groupname);

        return parent::showOutput($data);
    }
}

?>
