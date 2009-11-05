<?php
/**
 * Dynamic URL Title Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * @author mikespub <mikespub@xaraya.com>
*/
include_once "modules/base/xarproperties/Dynamic_TextBox_Property.php";

/**
 * handle the URL + Title property
 *
 * @package dynamicdata
 *
 */
class Dynamic_URLTitle_Property extends Dynamic_TextBox_Property
{
    function checkInput($name='', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return $this->validateValue($value);
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            if (is_array($value)) {

                if (isset($value['title'])) {
                    $title = $value['title'];
                } else {
                    $title = '';
                }

                if (isset($value['link'])) {
                    $link = $value['link'];
                } else {
                    $link = '';
                }
                // Make sure $value['title'] is set and has a length > 0
                if (strlen(trim($title))) {
                    $title = $value['title'];
                } else {
                    $title = '';
                }
                //check the link
                // linoj: changed the following to work same way as Dynamic_URL_Property and URLIcon_Property, leaving old code here for reference (this may be better??)
                $link = trim($link);
                // Make sure $value['link'] is set, has a length > 0 and does not equal simply 'http://'
                if (!empty($link) && $link != 'http://')  {
                   //let's process futher then
                   //check it is not invalid eg html tag
                    if (preg_match('/[<>"]/',$link)) {
                        $this->invalid = xarML('URL');
                        $this->value = null;
                        return false;
                    } else {
                      // If we have a scheme but nothing following it,
                        // then consider the link empty :-)
                        if (preg_match('!^[a-z]+\:\/\/$!i', trim($link))) {
                            $link = '';
                        } else {
                            // Do some URL validation below. Separate for better understanding
                            // Still not perfect. Add as seen fit.
                            $uri = parse_url($value['link']);
                            //Could it be a mailto?
                            if (isset($uri['scheme']) && ($uri['scheme'] == 'mailto')
                                && isset($uri['path']) && strstr(trim($uri['path']),'@')) {
                                $link = $value['link'];

                            } elseif (!isset($uri['scheme']) && !isset($uri['host']) && isset($uri['path'])
                                      && preg_match('!^www.!',trim($uri['path']))){
                                //bug 5959 allow users to enter http address without http
                                //jojodee: Just www now. But still, sure we want to make this guess and force http here?
                                 $link = 'http://'.$link;

                            // it has at least a scheme (http/ftp/etc) and a host (domain.tld)
                            } elseif (!isset($uri['scheme']) || empty($uri['scheme']) ||
                                   !isset($uri['host']) || empty($uri['host']))    {
                                $this->invalid = xarML('URL');
                                $this->value = null;
                                return false;
                            }
                        }

                    } //end checks for other schemes
                }
                //end check the link
                $value = array('link' => $link, 'title' => $title);
                $this->value = serialize($value);
            } else {
            // TODO: do we need to check the serialized content here ?
                $this->value = $value;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

//    function showInput($name = '', $value = null, $size = 0, $maxlength = 0, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        // empty value is allowed here
        if (!isset($value)) {
            $value = $this->value;
        }
        // empty fields are not allowed here
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        if (empty($size)) {
            $size = $this->size;
        }
        if (empty($maxlength)) {
            $maxlength = $this->maxlength;
        }
        // extract the link and title information
        if (empty($value)) {
        } elseif (is_array($value)) {
            if (isset($value['link'])) {
                $link = $value['link'];
            }
            if (isset($value['title'])) {
                $title = $value['title'];
            }
        } elseif (is_string($value) && substr($value,0,2) == 'a:') {
            $newval = unserialize($value);
            if (isset($newval['link'])) {
                $link = $newval['link'];
            }
            if (isset($newval['title'])) {
                $title = $newval['title'];
            }
        }
        if (empty($link)) {
            $link = 'http://';
        }
        if (empty($title)) {
            $title = '';
        }
        $data=array();

/*        return '<input type="text" name="' . $name . '[title]" value="'. xarVarPrepForDisplay($title) . '" size="'. $size . '" maxlength="'. $maxlength . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' /> <br />' .
               '<input type="text" name="' . $name . '[link]" value="'. xarVarPrepForDisplay($link) . '" size="'. $size . '" maxlength="'. $maxlength . '" />' .
               (!empty($link) && $link != 'http://' ? ' [ <a href="'.$link.'" target="preview">'.xarML('check').'</a> ]' : '') .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
*/
        $data['name']     = $name;
        $data['id']       = $id;
        $data['title']    = xarVarPrepForDisplay($title);
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']     = !empty($size) ? $size : $this->size;
        $data['link']     = xarVarPrepForDisplay($link);

        $template="";
        return xarTplProperty('base', 'urltitle', 'showinput', $data);
    }

    function showOutput($args = array())
    {
         extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $returndata= '';
        }
        if (is_array($value)) {
            if (isset($value['link'])) {
                $link = $value['link'];
            }
            if (isset($value['title'])) {
                $title = $value['title'];
            }
        } elseif (is_string($value) && substr($value,0,2) == 'a:') {
            $newval = unserialize($value);
            if (isset($newval['link'])) {
                $link = $newval['link'];
            }
            if (isset($newval['title'])) {
                $title = $newval['title'];
            }
        }
        $data=array();

        if (empty($link) && empty($title)) {
            //return '';
        } elseif (empty($link)) {
            $title = xarVarPrepForDisplay($title);
            //return  $title;
        } elseif (empty($title)) {
            $link = xarVarPrepForDisplay($link);
            //return '<a href="'.$link.'">'.$link.'</a>';
        } else {
            $title = xarVarPrepForDisplay($title);
            $link = xarVarPrepForDisplay($link);
            //return '<a href="'.$link.'">'.$title.'</a>';
        }

        $data['value']   = $this->value;
        $data['link']    = (!empty($link) && $link != 'http://') ? $link : '';
        $data['title']   = (!empty($title)) ? $title : '';

        $template="";
        return xarTplProperty('base', 'urltitle', 'showoutput', $data);
    }

    /**
     * Get the base information for this property.
     *
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $baseInfo = array(
                              'id'         => 41,
                              'name'       => 'urltitle',
                              'label'      => 'URL + Title',
                              'format'     => '41',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => '',
                            'aliases' => '',
                            'args'         => '',
                            // ...
                           );
        return $baseInfo;
     }

}
?>