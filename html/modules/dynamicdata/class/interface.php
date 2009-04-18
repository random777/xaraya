<?php
/**
 * Dynamic Object Interface 
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 * @todo <mikespub> move all object_* methods to other object classes ... or not ?
 * @todo try to replace xarTplModule with xarTplObject
 */

 sys::import('modules.dynamicdata.class.objects');
/**
 * Dynamic Object Interface (experimental - cfr. 'object' module)
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata
 */
class DataObjectInterface extends Object
{
    public $args = array();
    public $object = null;
    public $list = null;

    // module where the main templates for the GUI reside (defaults to the object module)
    public $tplmodule = null;
    // main function handling all object method calls (to be handled by the core someday ?)
    public $func = 'main';

    function __construct(array $args = array())
    {
        // set a specific GUI module for now
        if (!empty($args['tplmodule'])) {
            $this->tplmodule = $args['tplmodule'];
        }

        // get some common URL parameters
        if (!xarVarFetch('object',   'isset', $args['object'],   NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('module',   'isset', $args['module'],   NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('itemtype', 'isset', $args['itemtype'], NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('layout',   'isset', $args['layout'],   NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('template', 'isset', $args['template'], NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('startnum', 'isset', $args['startnum'], NULL, XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('numitems', 'isset', $args['numitems'], NULL, XARVAR_DONT_SET)) {return;}

         if (!xarVarFetch('fieldlist', 'isset', $fieldlist, NULL, XARVAR_DONT_SET)) {return;}
        // make fieldlist an array, 
        // @todo should the object class do it?
        if (!empty($fieldlist)) {
            $args['fieldlist'] = explode(',',$fieldlist);
        }

        // retrieve the object information for this object
        if(!empty($args['object']))  {
            $info = DataObjectMaster::getObjectInfo(
                array('name' => $args['object'])
            );
            $args = array_merge($args, $info);
        } elseif (!empty($args['module']) && empty($args['moduleid'])) { 
            $args['moduleid'] = xarMod::getRegID($args['module']);
        }

        // fill in the default object variables
        $this->args = $args;
    }


    function handle(array $args = array())
    {
        if(!xarVarFetch('method', 'isset', $args['method'], NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('itemid', 'isset', $args['itemid'], NULL, XARVAR_DONT_SET)) 
            return;
            
        if(empty($args['method'])) 
        {
            if(empty($args['itemid'])) 
                $args['method'] = 'view';
            else 
                $args['method'] = 'display';
        }
        // TODO: check for the presence of existing module functions to handle this if necessary
        switch ($args['method']) 
        {
            case 'new':
            case 'create':
                return $this->object_create($args);
            case 'modify':
            case 'update':
                return $this->object_update($args);
            case 'delete':
                return $this->object_delete($args);
            case 'display':
                return $this->object_display($args);
            case 'view':
            default:
                return $this->object_view($args);
        }
    }

    function object_create(array $args = array())
    {
        if(!xarVarFetch('preview', 'isset', $args['preview'], NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('confirm', 'isset', $args['confirm'], NULL, XARVAR_DONT_SET)) 
            return;

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        if(!isset($this->object)) 
        {
            $this->object =& DataObjectMaster::getObject($this->args);
            if(empty($this->object)) 
                return;
            if(empty($this->tplmodule)) 
            {
                $modinfo = xarModGetInfo($this->object->moduleid);
                $this->tplmodule = $modinfo['name'];
            }
        }
        if(!xarSecurityCheck(
            'AddDynamicDataItem',1,'Item',
            $this->object->moduleid.':'.$this->object->itemtype.':All')
        )   return;

        //$this->object->getItem();

        if(!empty($args['preview']) || !empty($args['confirm'])) 
        {
            if(!xarSecConfirmAuthKey()) 
                return;

            $isvalid = $this->object->checkInput();

            if($isvalid && !empty($args['confirm'])) 
            {
                $itemid = $this->object->createItem();

                if(empty($itemid)) 
                    return; // throw back

                if(!xarVarFetch('return_url',  'isset', $args['return_url'], NULL, XARVAR_DONT_SET)) 
                    return;
                if(!empty($args['return_url'])) 
                {
                    xarResponse::Redirect($args['return_url']);
                } 
                else 
                {
                    xarResponse::Redirect(xarModURL(
                        $this->tplmodule, 'user', $this->func,
                        array('object' => $this->object->name))
                    );
                }
                // Return
                return true;
            }
        }

        $title = xarML('New #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        // call item new hooks for this item
        $item = array();
        foreach(array_keys($this->object->properties) as $name) 
            $item[$name] = $this->object->properties[$name]->value;

        if(!isset($modinfo)) 
            $modinfo = xarModGetInfo($this->object->moduleid);

        $item['module'] = $modinfo['name'];
        $item['itemtype'] = $this->object->itemtype;
        $item['itemid'] = $this->object->itemid;
        $hooks = xarModCallHooks('item', 'new', $this->object->itemid, $item, $modinfo['name']);

        $this->object->viewfunc = $this->func;
        return xarTplModule(
            $this->tplmodule,'admin','new',
            array(
                'object' => $this->object,
                'preview' => $args['preview'],
                'hookoutput' => $hooks
            ),
            $this->object->template
        );
    }

    function object_update(array $args = array())
    {
        if(!xarVarFetch('preview', 'isset', $args['preview'], NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('confirm', 'isset', $args['confirm'], NULL, XARVAR_DONT_SET)) 
            return;

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        if(!isset($this->object)) 
        {
            $this->object =& DataObjectMaster::getObject($this->args);
            if(empty($this->object)) 
                return;
            if(empty($this->tplmodule)) 
            {
                $modinfo = xarModGetInfo($this->object->moduleid);
                $this->tplmodule = $modinfo['name'];
            }
        }
        if(!xarSecurityCheck(
            'EditDynamicDataItem',1,'Item',
            $this->object->moduleid.':'.$this->object->itemtype.':'.$this->object->itemid)
        ) return;

        $itemid = $this->object->getItem();
        if(empty($itemid) || $itemid != $this->object->itemid) 
            throw new BadParameterException(null,'The itemid updating the object was found to be invalid');

        if(!empty($args['preview']) || !empty($args['confirm'])) 
        {
            if(!xarSecConfirmAuthKey()) 
                return;

            $isvalid = $this->object->checkInput();

            if($isvalid && !empty($args['confirm'])) 
            {
                $itemid = $this->object->updateItem();

                if(empty($itemid)) 
                    return; // throw back

                if(!xarVarFetch('return_url',  'isset', $args['return_url'], NULL, XARVAR_DONT_SET)) 
                    return;
                    
                if(!empty($args['return_url'])) 
                    xarResponse::Redirect($args['return_url']);
                else 
                    xarResponse::Redirect(xarModURL(
                        $this->tplmodule, 'user', $this->func,
                        array('object' => $this->object->name))
                    );
                // Return
                return true;
            }
        }

        $title = xarML('Modify #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        // call item new hooks for this item
        $item = array();
        foreach(array_keys($this->object->properties) as $name) 
            $item[$name] = $this->object->properties[$name]->value;

        if(!isset($modinfo)) 
            $modinfo = xarModGetInfo($this->object->moduleid);

        $item['module'] = $modinfo['name'];
        $item['itemtype'] = $this->object->itemtype;
        $item['itemid'] = $this->object->itemid;
        $hooks = xarModCallHooks(
            'item', 'modify', $this->object->itemid, 
            $item, $modinfo['name']
        );

        $this->object->viewfunc = $this->func;
        return xarTplModule(
            $this->tplmodule,'admin','modify',
            array(
                'object' => $this->object,
                'hookoutput' => $hooks
            ),
            $this->object->template
        );
    }

    function object_delete(array $args = array())
    {
        if(!xarVarFetch('cancel',  'isset', $args['cancel'],  NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('confirm', 'isset', $args['confirm'], NULL, XARVAR_DONT_SET)) 
            return;

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        if(!isset($this->object)) 
        {
            $this->object =& DataObjectMaster::getObject($this->args);
            if(empty($this->object)) 
                return;

            if(empty($this->tplmodule)) 
            {
                $modinfo = xarModGetInfo($this->object->moduleid);
                $this->tplmodule = $modinfo['name'];
            }
        }
        if(!empty($args['cancel'])) 
        {
            if(!xarVarFetch('return_url',  'isset', $args['return_url'], NULL, XARVAR_DONT_SET)) 
                return;
                
            if(!empty($args['return_url'])) 
                xarResponse::Redirect($args['return_url']);
            else 
                xarResponse::Redirect(xarModURL(
                    $this->tplmodule, 'user', $this->func,
                    array('object' => $this->object->name))
                );
            // Return
            return true;
        }
        
        if(!xarSecurityCheck(
            'DeleteDynamicDataItem',1,'Item',
            $this->object->moduleid.':'.$this->object->itemtype.':'.$this->object->itemid)
        ) return;

        $itemid = $this->object->getItem();
        if(empty($itemid) || $itemid != $this->object->itemid) 
            throw new BadParameterException(null,'The itemid when deleting the object was found to be invalid');

        if(!empty($args['confirm'])) 
        {
            if(!xarSecConfirmAuthKey()) 
                return;

            $itemid = $this->object->deleteItem();

            if(empty($itemid)) 
                return; // throw back

            if(!xarVarFetch('return_url',  'isset', $args['return_url'], NULL, XARVAR_DONT_SET)) 
                return;
                
            if(!empty($args['return_url'])) 
                xarResponse::Redirect($args['return_url']);
            else 
                xarResponse::Redirect(xarModURL(
                    $this->tplmodule, 'user', $this->func,
                    array('object' => $this->object->name))
                );
            // Return
            return true;
        }

        $title = xarML('Delete #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        $this->object->viewfunc = $this->func;
        return xarTplModule(
            $this->tplmodule,'admin','delete',
            array('object' => $this->object),
            $this->object->template
        );
    }

    function object_display(array $args = array())
    {
        if(!xarVarFetch('preview', 'isset', $args['preview'], NULL, XARVAR_DONT_SET)) 
            return;

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        if(!isset($this->object)) 
        {
            $this->object =& DataObjectMaster::getObject($this->args);
            if(empty($this->object)) 
                return;

            if(empty($this->tplmodule)) 
            {
                $modinfo = xarModGetInfo($this->object->moduleid);
                $this->tplmodule = $modinfo['name'];
            }
        }
        $title = xarML('Display #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        $itemid = $this->object->getItem();
        if(empty($itemid) || $itemid != $this->object->itemid) 
            throw new BadParameterException(
                null,
                'The itemid when displaying the object was found to be invalid'
            );

        // call item display hooks for this item
        $item = array();
        foreach(array_keys($this->object->properties) as $name) 
            $item[$name] = $this->object->properties[$name]->value;

        if(!isset($modinfo)) 
            $modinfo = xarModGetInfo($this->object->moduleid);

        $item['module'] = $modinfo['name'];
        $item['itemtype'] = $this->object->itemtype;
        $item['itemid'] = $this->object->itemid;
        $item['returnurl'] = xarModURL(
            $this->tplmodule,'user',$this->func,
            array(
                'object' => $this->object->name,
                'itemid'   => $this->object->itemid
            )
        );
        $hooks = xarModCallHooks(
            'item', 'display', $this->object->itemid, $item, $modinfo['name']
        );

        $this->object->viewfunc = $this->func;
        return xarTplModule(
            $this->tplmodule,'user','display',
            array(
                'object' => $this->object,
                'hookoutput' => $hooks
            ),
            $this->object->template
        );
    }

    // no longer needed
    function object_list(array $args = array())
    {
        if(!xarVarFetch('catid',    'isset', $args['catid'],    NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('sort',     'isset', $args['sort'],     NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('startnum', 'isset', $args['startnum'], NULL, XARVAR_DONT_SET)) 
            return;

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        if(!isset($this->list)) 
        {
            $this->list =& DataObjectMaster::getObjectList($this->args);
            if(empty($this->list)) 
                return;
            
            if(empty($this->tplmodule)) 
            {
                $modinfo = xarModGetInfo($this->list->moduleid);
                $this->tplmodule = $modinfo['name'];
            }
        }
        $title = xarML('List #(1)', $this->list->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        $this->list->getItems();

        $this->list->viewfunc = $this->func;
        $this->list->linkfunc = $this->func;
        return xarTplModule(
            $this->tplmodule,'admin','view',
            array('object' => $this->list),
            $this->list->template
        );
    }

    function object_view(array $args = array())
    {
        if(!xarVarFetch('catid',    'isset', $args['catid'],    NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('sort',     'isset', $args['sort'],     NULL, XARVAR_DONT_SET)) 
            return;
        if(!xarVarFetch('startnum', 'isset', $args['startnum'], NULL, XARVAR_DONT_SET)) 
            return;

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        if(!isset($this->list)) 
        {
            $this->list =& DataObjectMaster::getObjectList($this->args);
            if(empty($this->list)) 
                return;

            if(empty($this->tplmodule)) 
            {
                $modinfo = xarModGetInfo($this->list->moduleid);
                $this->tplmodule = $modinfo['name'];
            }
        }
        $title = xarML('View #(1)', $this->list->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        $this->list->getItems();

        $this->list->viewfunc = $this->func;
        $this->list->linkfunc = $this->func;
        return xarTplModule(
            $this->tplmodule,'user','view',
            array('object' => $this->list),
            $this->list->template
        );
    }

}

?>
