#!/usr/bin/env php
<?php
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
set_time_limit(0);

include_once dirname(__FILE__) . '/build.config.php';


// Инициализируем MODx
include_once $sources['root'] . 'www/core/config/config.inc.php';
include_once $sources['root'] . 'www/core/model/modx/modx.class.php';

$modx= new modX();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

//Здесь мы генерируем xml схему
include dirname(__FILE__).'/build.writeschema.php';

//Здесь мы парсим схему и генерируем все необходимые модели
include dirname(__FILE__).'/build.schema.php';

//Подгружаем класс для сборки пакетов
$modx->loadClass('transport.modPackageBuilder','',false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage($package_name,'0.1','');
$builder->registerNamespace($package_name,false,true,"{core_path}components/$package_name/");

//Затем начинаем процесс по упаковке и переносу