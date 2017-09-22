<?

//If you need to update only builder component files - set COMPONENT_BUILD to true.
define("COMPONENT_BUILD", true);

$root = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
$builderRoot = $root . "modxbuilder/";

$buildConfig = array(
    "package_name" => "mycomponent",
    "package_version" => "0.1",
    "package_release" => "",
    "package_table_prefix" => "my_",
    "package_class_prefix" => "my",

    "regenerate_schema" => true,
    //switch to false if you don't need to rewrite your class-files
    "regenerate_classes" => true,
    //switch to false if you don't need to rewrite your map.inc-files
    "regenerate_maps" => true,

    "modx_root" => $root . "www/",
    "builder_root" => $builderRoot,
    "tools_root" => $builderRoot . "tools/",
);

$builderComponentRoot = $buildConfig["builder_root"] . $buildConfig['package_name'] . '/';

$sources = array();

if (COMPONENT_BUILD)
{
    $buildConfig = array_merge($buildConfig, array(
        "root" => $root,
        "build" => $builderComponentRoot . "build/",
        "resolvers" => $builderComponentRoot . "build/resolvers/",
        "data" => $builderComponentRoot . "build/data/",
        "source_core" => $root . "www/core/components/{$buildConfig['package_name']}/",
        "lexicon" => $root . "www/core/components/{$buildConfig['package_name']}/lexicon/",
        "source_assets" => $root . "www/assets/components/{$buildConfig['package_name']}/",
        "docs" => $root . "core/components/{$buildConfig['package_name']}/docs/",

        "package_dir" => $builderComponentRoot . "core/components/{$buildConfig['package_name']}",
        "model_dir" => $builderComponentRoot . "core/components/{$buildConfig['package_name']}/model",
        "class_dir" => $builderComponentRoot . "core/components/{$buildConfig['package_name']}/model/{$buildConfig['package_name']}",
        "schema_dir" => $builderComponentRoot . "core/components/{$buildConfig['package_name']}/model/schema",
        "mysql_class_dir" => $builderComponentRoot . "core/components/{$buildConfig['package_name']}/model/{$buildConfig['package_name']}/mysql",

        //It's a main file we edit
        "xml_schema_file" => $builderComponentRoot . "core/components/{$buildConfig['package_name']}/model/schema/{$buildConfig['package_name']}.mysql.schema.xml",

        //It's a new file generated automatically. We will transfer new code to file above
        "new_xml_schema_file" => $builderComponentRoot . "core/components/{$buildConfig['package_name']}/model/schema/{$buildConfig['package_name']}.mysql.schema.new.xml"
    ));
}
else
{
    $buildConfig = array_merge($buildConfig, array(
        "root" => $root,
        "build" => $builderComponentRoot . "build/",
        "resolvers" => $builderComponentRoot . "build/resolvers/",
        "data" => $builderComponentRoot . "build/data/",
        "source_core" => $root . "www/core/components/{$buildConfig['package_name']}/",
        "lexicon" => $root . "www/core/components/{$buildConfig['package_name']}/lexicon/",
        "source_assets" => $root . "www/assets/components/{$buildConfig['package_name']}/",
        "docs" => $root . "www/core/components/{$buildConfig['package_name']}/docs/",

        "package_dir" => $root . "core/components/{$buildConfig['package_name']}",
        "model_dir" => $root . "core/components/{$buildConfig['package_name']}/model",
        "class_dir" => $root . "core/components/{$buildConfig['package_name']}/model/{$buildConfig['package_name']}",
        "schema_dir" => $root . "core/components/{$buildConfig['package_name']}/model/schema",
        "mysql_class_dir" => $root . "core/components/{$buildConfig['package_name']}/model/{$buildConfig['package_name']}/mysql",

        //It's a main file we edit
        "xml_schema_file" => $root . "core/components/{$buildConfig['package_name']}/model/schema/{$buildConfig['package_name']}.mysql.schema.xml",

        //It's a new file generated automatically. We will transfer new code to file above
        "new_xml_schema_file" => $root . "core/components/{$buildConfig['package_name']}/model/schema/{$buildConfig['package_name']}.mysql.schema.new.xml"
    ));
}

unset($root);

//Объявляем базовые константы
define("MODX_CORE_PATH", $buildConfig['root'] . 'www/core/');
define("MODX_BASE_PATH", $buildConfig['root'] . 'www/');
define('MODX_BASE_URL', '/');

return $buildConfig;