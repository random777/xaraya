<?php
// TODO: turn this into an xml file
    function blocks_dataapi_adminmenu()
    {
        return array(
                array('includes' => array('main','overview'), 'target' => 'main', 'label' => xarML('Blocks Overview')),
                array('mask' => 'EditBlock', 'includes' => array('view_instances','modify_instance','delete_instance'), 'target' => 'view_instances', 'title' => xarML('View or edit all block instances'), 'label' => xarML('View Blocks')),
                array('mask' => 'AddBlock', 'includes' => 'new_instance', 'target' => 'new_instance', 'title' => xarML('Add a new block instance'), 'label' => xarML('New Block')),
                array('mask' => 'AdminBlock', 'includes' => 'view_types', 'target' => 'view_types', 'label' => xarML('Block Types')),
                array('mask' => 'DeleteBlock', 'includes' => array('view_groups','modify_group','delete_group'), 'target' => 'view_groups', 'title' => xarML('View the defined block groups'), 'label' => xarML('Block Groups')),
                array('mask' => 'AdminBlock', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Add a new block type into the system'), 'label' => xarML('Modify Configuration')),
        );
    }
?>