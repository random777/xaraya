<?php
/**
 * Return relationship information
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Return relationship information (test only)
 */
function dynamicdata_util_relations($args)
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData')) return;

    if(!xarVarFetch('module',    'isset', $module,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('module_id', 'isset', $module_id, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype',  'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('objectid',  'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',     'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('field',     'isset', $field,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('value',     'isset', $value,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('relation',  'isset', $relation,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('direction', 'isset', $direction, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('withobjectid', 'isset', $withobjectid, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('withtable', 'isset', $withtable, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('withfield', 'isset', $withfield, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('withvalue', 'isset', $withvalue, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('confirm',   'isset', $confirm,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('update',    'isset', $update,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('delete',    'isset', $delete,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('what',      'isset', $what,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('extra',     'isset', $extra,     NULL, XARVAR_DONT_SET)) {return;}

    // filter out invalid tables
    $xartables = xarDB::getTables();
    if (!empty($table)) {
        if ($table == 'dummy' || substr($table,0,15) == 'module variable') {
            $table = null;
        } elseif ($table == 'dynamic_data') {
            $table = $xartables['dynamic_data'];
        }
    }

    // prepare template variables
    $data = array('module_id' => $module_id,
                  'itemtype' => $itemtype,
                  'objectid' => $objectid,
                  'table' => $table,
                  'field' => $field,
                  'value' => $value,
                  'relation' => $relation,
                  'direction' => $direction,
                  'withobjectid' => $withobjectid,
                  'withtable' => $withtable,
                  'withfield' => $withfield,
                  'withvalue' => $withvalue,
                  'extra' => $extra);

    // get objects
    $data['objects'] = xarMod::apiFunc('dynamicdata','user','getobjects');

    // import the DataObjectLinks class
    sys::import('modules.dynamicdata.class.objects.links');

    // get linktypes
    $data['linktypes'] = DataObjectLinks::$linktypes;

    // get tables
    $dbconn = xarDB::getConn();
    $dbInfo = $dbconn->getDatabaseInfo();
    // Pass the full info object to the template, let them figure out how and what
    $data['tables'] = $dbInfo->getTables();

    // get mapping of objects to datasources by looking at property sources
    if (empty($objectid) && empty($table)) {
        $data['mapping'] = DataObjectLinks::getMapping();
    }

    //dynamicdata_sync_relations();

    if (!empty($objectid)) {
        $object = xarMod::apiFunc('dynamicdata','user','getobject',
                                array('objectid' => $objectid));
        $data['object'] = $object;
        $data['fields'] = $object->properties;

        // get all links, including 'info' for reverse one-way information
        $links = DataObjectLinks::getLinks($object,'all');
        if (!empty($links[$object->name])) {
            $data['relations'] = $links[$object->name];
        } else {
            $data['relations'] = array();
        }
        // FIXME: remove initialization of modvar after next release
        xarModVars::set('dynamicdata', 'getlinkedobjects', 0);

        if (!empty($withobjectid)) {
            $withobject = xarMod::apiFunc('dynamicdata','user','getobject',
                                        array('objectid' => $withobjectid));
            $data['withobject'] = $withobject;
            $data['withfields'] = $withobject->properties;
        }
        if (!empty($confirm)) {
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }
/* no longer in use (for now ?)
            if (!empty($value)) {
                $field = $value;
            }
            if (!empty($withvalue)) {
                $withfield = $withvalue;
            }
*/
            if (empty($direction)) {
                $direction = 'bi';
            }
            if (empty($extra)) {
                $extra = '';
            }

            // add link
            DataObjectLinks::addLink($objectid, $field, $withobjectid, $withfield, $relation, $direction, $extra);
            xarResponse::Redirect(xarModURL('dynamicdata', 'util', 'relations',
                                            array('objectid' => $objectid)));
            return true;

        } elseif (!empty($delete) && !empty($what)) {
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }
            // remove selected link(s)
            foreach ($what as $link_id => $val) {
                if (empty($link_id) || empty($val)) continue;
                DataObjectLinks::removeLink($link_id);
            }
            xarResponse::Redirect(xarModURL('dynamicdata', 'util', 'relations',
                                            array('objectid' => $objectid)));
            return true;

        } elseif (!empty($update)) {
            if(!xarVarFetch('getlinkedobjects', 'isset', $getlinkedobjects, NULL, XARVAR_DONT_SET)) {return;}
            if (!empty($getlinkedobjects)) {
                xarModItemVars::set('dynamicdata', 'getlinkedobjects', 1, $objectid);
            } else {
                xarModItemVars::set('dynamicdata', 'getlinkedobjects', 0, $objectid);
            }
        }

        // get fieldtype property to show object properties
        $data['prop'] = xarMod::apiFunc('dynamicdata','user','getproperty',
                                        array('type' => 'fieldtype',
                                              'name' => 'dummy'));

    } elseif (!empty($table)) {
        $object = xarMod::apiFunc('dynamicdata','user','getobject',
                                array('table' => $table));
        $data['fields'] = $object->properties;

        sys::import('modules.dynamicdata.class.datastores.links');

        // get all links, including 'info' for reverse one-way information
        $links = DataStoreLinks::getLinks($table,'all');
        if (!empty($links[$table])) {
            $data['relations'] = $links[$table];
        } else {
            $data['relations'] = array();
        }

        // get foreign keys for tables
        $data['foreignkeys'] = DataStoreLinks::getForeignKeys();

        if (!empty($withtable)) {
            $withobject = xarMod::apiFunc('dynamicdata','user','getobject',
                                        array('table' => $withtable));
            $data['withfields'] = $withobject->properties;
        }
        if (!empty($confirm)) {
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }        
/* no longer in use (for now ?)
            if (!empty($value)) {
                $field = $value;
            }
            if (!empty($withvalue)) {
                $withfield = $withvalue;
            }
*/
            if (empty($direction)) {
                $direction = 'bi';
            }
            if (empty($extra)) {
                $extra = '';
            }
            // CHECKME: always bi-directional for tables ?
            $direction = 'bi';
            DataStoreLinks::addLink($table, $field, $withtable, $withfield, $relation, $direction, $extra);
            xarResponse::Redirect(xarModURL('dynamicdata', 'util', 'relations',
                                          array('table' => $table)));
            return true;

        } elseif (!empty($delete) && !empty($what)) {
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            // remove selected link(s)
            foreach ($what as $link_id => $val) {
                if (empty($link_id) || empty($val)) continue;
                DataStoreLinks::removeLink($link_id);
            }
            xarResponse::Redirect(xarModURL('dynamicdata', 'util', 'relations',
                                          array('table' => $table)));
            return true;
        }

        // get fieldtype property to show table fields
        $data['prop'] = xarMod::apiFunc('dynamicdata','user','getproperty',
                                        array('type' => 'fieldtype',
                                              'name' => 'dummy'));

    } elseif (!empty($module_id)) {
        $data['module'] = xarMod::getName($module_id);
        // (try to) get the relationships between this module and others
        $data['relations'] = xarMod::apiFunc('dynamicdata','util','getrelations',
                                           array('module_id' => $module_id,
                                                 'itemtype' => $itemtype));
    }

    if (!isset($data['relations']) || $data['relations'] == false) {
        $data['relations'] = array();
    }

    xarTplSetPageTemplateName('admin');

    return $data;
}

function dynamicdata_sync_relations()
{
/*
    // add foreign keys to table links

    sys::import('modules.dynamicdata.class.datastores.links');

    $foreignkeys = DataStoreLinks::getForeignKeys();
    foreach ($foreignkeys as $info) {
        DataStoreLinks::addLink($info['source'], $info['from'], $info['target'], $info['to'], 'parents', 'fk');
    }
*/

/*
    // sync object links with table links

    sys::import('modules.dynamicdata.class.objects.links');

    $tablelinks = DataStoreLinks::getLinks();

    // get source mapping
    $sourcemapping = DataStoreLinks::getSourceFieldMapping();

    foreach ($tablelinks as $source => $links) {
        foreach ($links as $link) {
            $fromsource = $link['source'].'.'.$link['from_prop'];
            $totarget = $link['target'].'.'.$link['to_prop'];
            if (!empty($sourcemapping[$fromsource]) && !empty($sourcemapping[$totarget])) {
                $fromprop = $sourcemapping[$fromsource];
                $toprop = $sourcemapping[$totarget];
                // force bi-directional object relationship for foreign keys
                if ($link['direction'] == 'fk') {
                    if ($link['link_type'] == 'parents') {
                        DataObjectLinks::addLink($fromprop['objectid'], $fromprop['name'], $toprop['objectid'], $toprop['name'], 'parents', 'bi');
                    }
 
                // CHECKME: assume uni-directional object relationship from child for other table relationships ?
                } elseif ($link['dir'] == 'bi') {
                    if ($link['type'] == 'parents') {
                        DataObjectLinks::addLink($fromprop['objectid'], $fromprop['name'], $toprop['objectid'], $toprop['name'], 'parents', 'uni');
                    }

                // CHECKME: where would this come from ?
                } else {

                }
            }
        }
    }
*/

/*
    // sync object links with objectref properties

    // find all properties of type ObjectRef
    $properties = DataObjectMaster::getObjectList(array('name'  => 'properties',
                                                        'where' => 'type eq 507', // ObjectRefProperty
                                                        'fieldlist' => array('id','name','objectid')));
    $properties->getItems();
    $objectstocheck = array();
    foreach ($properties->items as $item) {
        $objectstocheck[$item['objectid']] = 1;
    }

    foreach (array_keys($objectstocheck) as $objectid) {
        $object = DataObjectMaster::getObject(array('objectid' => $objectid));
        $links = DataObjectLinks::getLinks($object,'all');
        $source = $object->name;
        if (empty($links[$source])) {
            $links[$source] = array();
        }
        foreach (array_keys($object->properties) as $propname) {
            if ($object->properties[$propname]->type != 507) continue;
            $from_prop = $propname;
            $target = $object->properties[$propname]->initialization_refobject;
            $to_prop = $object->properties[$propname]->initialization_store_prop;
            $found = 0;
            // see if we already have an object link corresponding to this objectref
            foreach ($links[$source] as $link) {
                if ($link['from_prop'] == $from_prop && $link['target'] == $target && $link['to_prop'] == $to_prop) {
                    $found = 1;
                    break;
                }
            }
            if (empty($found)) {
                // CHECKME: create bi-directional parents link to the other object here ?
                //DataObjectLinks::addLink($source, $from_prop, $target, $to_prop, 'linkedto', 'bi');
                DataObjectLinks::addLink($source, $from_prop, $target, $to_prop, 'parents', 'bi');
            }
        }
    }
*/
}

?>