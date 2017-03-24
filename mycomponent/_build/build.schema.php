<?php

$modx->loadClass('transport.modPackageBuilder','',false, true);
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

if (!is_dir($sources['model_dir'])) { $modx->log(modX::LOG_LEVEL_ERROR,'Model directory not found!'); die(); }
if (!file_exists($sources['xml_schema_file'])) { $modx->log(modX::LOG_LEVEL_ERROR,'Schema file not found!'); die(); }
$generator->parseSchema($sources["xml_schema_file"], $sources["model_dir"]."/");

$modx->log(modX::LOG_LEVEL_INFO, 'Done!');