<?php
/**
 * Example of setup options
 *
 * @package mypkg
 * @subpackage build
 */

/* set some default values */
$values = array(
    'someKey' => 'someValue',
);
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        /*
        $setting = $modx->getObject('modSystemSetting',array('key' => 'mypkg.someKey'));
        if ($setting != null) { $values['someKey'] = $setting->get('value'); }
        unset($setting);
        */
        break;
    case xPDOTransport::ACTION_UNINSTALL: break;
}

$output = '';
/*
$output = '<label for="mypkg-someKey">Some Key:</label>
<input type="text" name="someKey" id="mypkg-someKey" width="300" value="'.$values['someKey'].'" />
*/

return $output;