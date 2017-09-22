<?php

/**
 * создадим категорию для наших чанков и сниппетов
 */
$modx->log(xPDO::LOG_LEVEL_INFO,'Создаем категорию '.$package_name);

/* @var modCategory $category */
$category= $modx->newObject('modCategory');
$category->set('category',$package_name);

/**
 * Укажем  атрибуты для транспорта категории
 */
$attr = array(
    xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
);

/**
 * Добавляем сниппеты к категории
 */
if (defined('BUILD_SNIPPET_UPDATE'))
{
    $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Snippets'] = array(
        xPDOTransport::PRESERVE_KEYS => false,
        xPDOTransport::UPDATE_OBJECT => BUILD_SNIPPET_UPDATE,
        xPDOTransport::UNIQUE_KEY => 'name',
    );
    $snippets = include SOURCES_DATA_PATH . 'transport.snippets.php';
    if (!is_array($snippets))
    {
        $modx->log(modX::LOG_LEVEL_ERROR, 'Не могу получить сниппеты.');
    }
    else
    {
        $category->addMany($snippets);
        $modx->log(modX::LOG_LEVEL_INFO, 'Добавлено сниппетов: ' . count($snippets) . '.');
    }
}

/**
 * Добавляем чанки к категории
 */
if (defined('BUILD_CHUNK_UPDATE'))
{
    $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Chunks'] = array(
        xPDOTransport::PRESERVE_KEYS => false,
        xPDOTransport::UPDATE_OBJECT => BUILD_CHUNK_UPDATE,
        xPDOTransport::UNIQUE_KEY => 'name',
    );
    $chunks = include SOURCES_DATA_PATH . 'transport.chunks.php';
    if (!is_array($chunks))
    {
        $modx->log(modX::LOG_LEVEL_ERROR, 'Не могу добавить чанки.');
    }
    else
    {
        $category->addMany($chunks);
        $modx->log(modX::LOG_LEVEL_INFO, 'Добавлено чанков: ' . count($chunks) . '.');
    }
}


/**
 * Создаем транспорт и указываем особые параметры для транспорта
 */

//Транспорт для категории (и соответственно для всех объектов, которые мы привязали к этой категории)
$vehicle = $builder->createVehicle($category,$attr);

/**
 * Теперь покажем, какие файлы и куда нужно перенести при установке (resolvers)
 */

/* обращаем внимание, что в target стоит eval выражение */
$vehicle->resolve('file',array(
    'source' => SOURCES_CORE_PATH,
    'target' => "return MODX_CORE_PATH.'components/';",
));

$vehicle->resolve('file',array(
    'source' => SOURCES_ASSETS_PATH,
    'target' => "return MODX_ASSETS_PATH.'components/';",
));

/* кладем наш транспорт в пакет */
$builder->putVehicle($vehicle);

//Добавим транспорт для наших системных настроек
$attr = array(
    xPDOTransport::UNIQUE_KEY => 'key',
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => false,
);

$settings = include SOURCES_DATA_PATH.'transport.settings.php';

if (!is_array($settings)) {
    $modx->log(modX::LOG_LEVEL_ERROR,'Не могу добавить системные настройки.');
} else {
    foreach ($settings as $setting) {
        $vehicle = $builder->createVehicle($setting,$attr);
        $builder->putVehicle($vehicle);
    }
    $modx->log(modX::LOG_LEVEL_INFO,'Добавлено настроек: '.count($settings).'.');
}

/**
 * Добавим дополнительную информацию к нашему пакету
 */
$builder->setPackageAttributes(array(
    'changelog' => file_get_contents(SOURCES_DOCS_PATH . 'changelog.txt'),
    'license' => file_get_contents(SOURCES_DOCS_PATH . 'license.txt'),
    'readme' => file_get_contents(SOURCES_DOCS_PATH . 'readme.txt'),

    /*'setup-options' => array(
        'source' => SOURCES_BUILD_PATH.'setup.options.php',
    ),*/

));
$modx->log(modX::LOG_LEVEL_INFO,'Добавлены атрибуты к пакету!');

/**
 * Начинаем упаковку
 */

$modx->log(modX::LOG_LEVEL_INFO,'Начинаем запаковку пакета в zip-архив');

if($builder->pack()){
    $modx->log(modX::LOG_LEVEL_INFO,'Пакет успешно запакован!');
}
else{
    $modx->log(modX::LOG_LEVEL_ERROR,'Ошибка при создании архива пакета!');
}

if (!XPDO_CLI_MODE) {
    //Для удобства просмотра в браузере, выводим тег pre
    echo '</pre>';
}