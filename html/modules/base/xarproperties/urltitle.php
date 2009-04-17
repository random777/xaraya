<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.textbox');
/**
 * handle the URL + Title property
 */
class URLTitleProperty extends TextBoxProperty
{
    public $id         = 41;
    public $name       = 'urltitle';
    public $desc       = 'URL + Title';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template  = 'urltitle';
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

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

                // Make sure $value['link'] is set, has a length > 0 and does not equal simply 'http://'
                if (strlen(trim($link)) && trim($link) != 'http://') {
                        $link = $value['link'];
                } else {
                    // If we have a scheme but nothing following it,
                    // then consider the link empty :-)
                    if (eregi('^[a-z]+\:\/\/$', trim($link))) {
                        $link = '';
                    } else {

                        // Do some URL validation below - make sure the url
                        // has at least a scheme (http/ftp/etc) and a host (domain.tld)
                        $uri = parse_url($value['link']);

                        if ( (!isset($uri['scheme']) || empty($uri['scheme'])) ||
                            (!isset($uri['host']) || empty($uri['host']))) {
                                $this->invalid = xarML('URL');
                                $this->value = null;
                                return false;
                        }
                    }
                }
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

    public function showInput(Array $data = array())
    {
        if (!isset($data['value'])) {
            $value = $this->value;
        } else {
            $value = $data['value'];
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

        $data['title']    = xarVarPrepForDisplay($title);
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['link']     = xarVarPrepForDisplay($link);

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) $value = $this->value;

        if (empty($value)) $returndata= '';

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

        if (!empty($title)) $title = xarVarPrepForDisplay($title);

        $url_parts = parse_url($link);
        if (!isset($url_parts['host'])) {
            $truecurrenturl = xarServer::getCurrentURL(array(), false);
            $urldata = xarModAPIFunc('roles','user','parseuserhome',array('url'=>$link,'truecurrenturl'=>$truecurrenturl));
            $link = $urldata['redirecturl'];
        }

        $data['value']   = $this->value;
        $data['link']    = (!empty($link) && $link != 'http://') ? $link : '';
        $data['title']   = (!empty($title)) ? $title : '';

        return parent::showOutput($data);
    }
}
?>
