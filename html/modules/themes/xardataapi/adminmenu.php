<?php
// TODO: turn this into an xml file
    function themes_dataapi_adminmenu()
    {
        return array(
                array('includes' => array('main','overview'), 'target' => 'main', 'label' => xarML('Themes Overview')),
                array('mask' => 'AdminTheme', 'includes' => 'list', 'target' => 'list', 'title' => xarML('View installed themes on the system'), 'label' => xarML('View Themes')),
                array('mask' => 'AdminTheme', 'includes' => 'listtpltags', 'target' => 'listtpltags', 'title' => xarML('View the registered template tags.'), 'label' => xarML('View Template Tags')),
                array('mask' => 'AdminTheme', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Modify the configuration of the themes module'), 'label' => xarML('Modify Configuration')),
        );
    }
?>
