<?php
/**
 * @var xPDOTransport $object
 * @var xPDOVehicle $this
 */

if ($object->xpdo) {
    $modx =& $object->xpdo;

    //TODO put your package name here
    $packageName = 'mycomponent';
    $modelPath = $modx->getOption($packageName.'.core_path',null,$modx->getOption('core_path').'components/'.$packageName.'/').'model/';
    $modx->addPackage($packageName,$modelPath);
    $manager = $modx->getManager();

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            //TODO add your database related custom objects
            //$manager->createObjectContainer('myObjectClass');
            break;
        case xPDOTransport::ACTION_UPGRADE:
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            //$manager->removeObjectContainer('myObjectClass');
            break;
    }
}
return true;