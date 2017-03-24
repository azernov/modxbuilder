<?

define("COMPONENT_BUILD", true);//Поставить в true, если требуется сборка компонента и false, если необходимо обновить только модели

//TODO поменяйте на свои значения
$package_table_prefix = 'mycmp_';
$package_name = 'mycomponent';
$package_class_prefix = 'my';

$regenerate_schema = true;

//Поставить в false если не нужно перезаписывать файлы классов
$regenerate_classes = true;

//Поставить false, если не нужно перезаписывать map.inc файлы
$regenerate_maps = true;

$root = dirname(dirname(dirname(dirname(__FILE__)))).'/';

$sources = array();

if(COMPONENT_BUILD)
{
    $sources = array (
        "root" => $root,
        "build" => $root ."modxbuilder/$package_name/_build/",
        "resolvers" => $root . "modxbuilder/$package_name/_build/resolvers/",
        "data" => $root . "modxbuilder/$package_name/_build/data/",
        "source_core" => $root."www/core/components/$package_name/",
        "lexicon" => $root . "www/core/components/$package_name/lexicon/",
        "source_assets" => $root."www/assets/components/$package_name/",
        "docs" => $root."core/components/$package_name/docs/",

        "package_dir" => $root."modxbuilder/$package_name/_core/components/$package_name",
        "model_dir" => $root."modxbuilder/$package_name/_core/components/$package_name/model",
        "class_dir" => $root. "modxbuilder/$package_name/_core/components/$package_name/model/$package_name",
        "schema_dir" => $root. "modxbuilder/$package_name/_core/components/$package_name/model/schema",
        "mysql_class_dir" => $root. "modxbuilder/$package_name/_core/components/$package_name/model/$package_name/mysql",

        //Это основной файл, который мы правим ручками
        "xml_schema_file" => $root. "modxbuilder/$package_name/_core/components/$package_name/model/schema/$package_name.mysql.schema.xml",

        //Это новый, сгенерированный файл, из которого мы будем забирать изменения и вставлять их в основной файл
        "new_xml_schema_file" => $root. "modxbuilder/$package_name/_core/components/$package_name/model/schema/$package_name.mysql.schema.new.xml"
    );
}
else
{
    $sources = array (
        "root" => $root,
        "build" => $root ."modxbuilder/$package_name/_build/",
        "resolvers" => $root . "modxbuilder/$package_name/_build/resolvers/",
        "data" => $root . "modxbuilder/$package_name/_build/data/",
        "source_core" => $root."www/core/components/$package_name/",
        "lexicon" => $root . "www/core/components/$package_name/lexicon/",
        "source_assets" => $root."www/assets/components/$package_name/",
        "docs" => $root."www/core/components/$package_name/docs/",

        "package_dir" => $root."core/components/$package_name",
        "model_dir" => $root."core/components/$package_name/model",
        "class_dir" => $root. "core/components/$package_name/model/$package_name",
        "schema_dir" => $root. "core/components/$package_name/model/schema",
        "mysql_class_dir" => $root. "core/components/$package_name/model/$package_name/mysql",

        //Это основной файл, который мы правим ручками
        "xml_schema_file" => $root. "core/components/$package_name/model/schema/$package_name.mysql.schema.xml",

        //Это новый, сгенерированный файл, из которого мы будем забирать изменения и вставлять их в основной файл
        "new_xml_schema_file" => $root. "core/components/$package_name/model/schema/$package_name.mysql.schema.new.xml"
    );
}

unset($root);

//Объявляем базовые константы
define("MODX_CORE_PATH",$sources['root'].'www/core/');
define("MODX_BASE_PATH",$sources['root'].'www/');
define('MODX_BASE_URL', '/');