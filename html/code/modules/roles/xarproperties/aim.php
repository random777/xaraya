<?php
/**
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.textbox');

/**
 * Handle AIM property
 */
class AIMProperty extends TextBoxProperty
{
    public $id         = 29;
    public $name       = 'aim';
    public $desc       = 'AIM Screen Name';
    public $reqmodules = array('roles');

    public $initialization_icon_url;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template = 'aim';
        $this->filepath   = 'modules/roles/xarproperties';
        if (empty($this->initialization_icon_url)) {
            $this->initialization_icon_url = xarTplGetImage('contact/aim.png','roles');
        }
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!empty($value)) {
            if (!is_string($value)) {
                $this->invalid = xarML('AIM Address: #(1)', $this->name);
                $this->value = null;
                return false;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

    public function showInput(Array $data = array())
    {
        if(!isset($data['value'])) $data['value'] = $this->value;

        $data['link'] ='';
        if(!empty($data['value'])) {
            $data['link'] = 'aim:goim?screenname='.xarVarPrepForDisplay($data['value']).'&message='.xarML('Hello+Are+you+there?');
        }
        // $data['value'] is prepared for display by textbox
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
        $data['value'] = xarVarPrepForDisplay($data['value']);

        $data['link'] = '';
        if (!empty($data['value'])) {
            $data['link'] = 'aim:goim?screenname='.$data['value'].'&message='.xarML('Hello+Are+you+there?');
        }
        if (empty($data['image'])) {
            $data['image'] = $this->initialization_icon_url;
        }
        return parent::showOutput($data);
    }
}
?>
