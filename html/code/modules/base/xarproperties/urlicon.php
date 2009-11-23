<?php
/**
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.url');

/**
 * Handle the URLIcon property
 */
class URLIconProperty extends URLProperty
{
    public $id         = 27;
    public $name       = 'urlicon';
    public $desc       = 'URL Icon';

    public $initialization_icon_url = 'http://';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template = 'urlicon';
    }

    public function showOutput(Array $data = array())
    {;
        if (empty($data['value'])) $data['value'] = $this->value;
        if (empty($data['link'])) $data['link'] = '';

        if (!empty($data['value']) && $data['value'] != 'http://' && empty($data['link'])) {
            $data['link'] = xarVarPrepForDisplay($data['value']);
        }
        if (empty($data['icon'])) {
            /* We don't have a validated icon to display, use favicon */
            /* FIXME: getfavicon needs to send back nothing if the favicon doens't exist. */
            $data['icon'] = xarMod::apiFunc('base',
                                          'user',
                                          'getfavicon',
                                          array('url' => $data['value']));
            if (empty($data['icon'])) {
                /* we'll have to use the default system icon */
                $data['icon'] = xarTplGetImage('icons/go-home.png','base');
            }
        }
        return parent::showOutput($data);
    }
}
?>
