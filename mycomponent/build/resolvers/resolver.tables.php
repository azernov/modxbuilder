<?php
/**
 * @var xPDOTransport $object
 * @var xPDOVehicle $this
 */

if ($object->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            $modx =& $object->xpdo;
            /** @var modxBuilder $modxbuilder */
            $modxbuilder = $this->_modxbuilder;

            $packageName = $modxbuilder->config['package_name'];
            $modelPath = $modx->getOption($packageName.'.core_path',null,$modxbuilder->config['source_core']).'/model/';
            $modx->addPackage($packageName,$modelPath);
            $manager = $modx->getManager();
            //TODO add your database related custom objects
            //$manager->createObjectContainer('myObjectClass');
            break;
        case xPDOTransport::ACTION_UPGRADE:
            break;
    }
}
return true;