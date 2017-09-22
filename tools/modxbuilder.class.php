<?php

/**
 * Class modxBuilder
 * Class for building components, generates model-files and other
 */
class modxBuilder
{
    /**
     * @var modX $modx
     */
    protected $modx;
    protected $config = array();

    /** @var modPackageBuilder */
    public $builder;

    /** @var  xPDOGenerator_my */
    protected $generator;

    public function __construct(&$modx, $config)
    {
        $this->modx = &$modx;
        $this->config = array(//List default settings
        );
        $this->config = array_merge($this->config, $config);
    }

    protected function getManager()
    {
        $this->modx->getManager();
    }

    protected function getGenerator()
    {
        if (!$this->generator)
        {
            //Подключаем наш класс генератора
            include_once $this->config['modx_root'] . 'core/xpdo/om/mysql/xpdogenerator.class.php';
            include_once($this->config['tools_root'] . "xpdogenerator.class.php");
            $manager = $this->modx->getManager();
            $this->generator = new xPDOGenerator_my($manager);
        }
        return $this->generator;
    }

    /**
     * @param bool $restrict_prefix - If you specify a table prefix, you probably want this set to 'true'. E.g. if you
     * have custom tables alongside the modx_xxx tables, restricting the prefix ensures
     * that you only generate classes/maps for the tables identified by the $this->config['package_table_prefix'].
     * @param bool $verbose - if true, will print status info.
     * @param bool $debug - if true, will include verbose debugging info, including SQL errors.
     */
    public function writeSchema($restrict_prefix = true, $verbose = true, $debug = true)
    {
        if (!defined('MODX_CORE_PATH'))
        {
            $this->modx->log(MODX_LOG_LEVEL_ERROR, 'Reverse Engineering Error! MODX_CORE_PATH not defined! Did you include the correct config file?');
            exit;
        }

        // A few variables used to track execution times.
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $tstart = $mtime;

        // Validations
        if (empty($this->config['package_name']))
        {
            $this->modx->log(MODX_LOG_LEVEL_ERROR, "Reverse Engineering Error! The package_name cannot be empty!  Please adjust the configuration and try again.");
            exit;
        }

        // Create directories if necessary
        $dirs = array($this->config["package_dir"], $this->config["schema_dir"], $this->config["mysql_class_dir"], $this->config["class_dir"]);

        foreach ($dirs as $d)
        {
            if (!file_exists($d))
            {
                if (!mkdir($d, 0777, true))
                {
                    $this->modx->log(MODX_LOG_LEVEL_ERROR, sprintf('Reverse Engineering Error! Error creating %s. Create the directory (and its parents) and try again.', $d));
                    exit;
                }
            }
            if (!is_writable($d))
            {
                $this->modx->log(MODX_LOG_LEVEL_ERROR, sprintf('Reverse Engineering Error! The %s directory is not writable by PHP. Adjust the permissions and try again.', $d));
                exit;
            }
        }

        if ($verbose)
        {
            $this->modx->log(MODX_LOG_LEVEL_INFO, sprintf('Ok: The necessary directories exist and have the correct permissions inside of %s', $this->config["package_dir"]));
        }

// Delete/regenerate map files?
        if (file_exists($this->config["new_xml_schema_file"]) && !$this->config['regenerate_schema'] && $verbose)
        {
            $this->modx->log(MODX_LOG_LEVEL_INFO, sprintf('Ok: Using existing XML schema file: %s', $this->config["new_xml_schema_file"]));
        }

        // Set the package name and root path of that package
        $this->modx->setPackage($this->config['package_name'], $this->config["package_dir"]);
        $this->modx->setDebug($debug);

        //$generator = $manager->getGenerator();  // Станадртное получение mysql генератора
        $generator = $this->getGenerator();
        $generator->setClassPrefix($this->config['package_class_prefix']);

        //Use this to create an XML schema from an existing database
        if ($this->config['regenerate_schema'])
        {
            if (!file_exists($this->config['new_xml_schema_file']))
            {
                touch($this->config['new_xml_schema_file']);
            }
            $xml = $generator->writeSchema($this->config["new_xml_schema_file"], $this->config['package_name'], 'xPDOObject', '', $restrict_prefix, $this->config['package_table_prefix']);
            if ($verbose)
            {
                $this->modx->log(MODX_LOG_LEVEL_INFO, sprintf('Ok: XML schema file generated: %s', $this->config["new_xml_schema_file"]));
            }
        }

        // Use this to generate classes from your schema
        if ($this->config['regenerate_classes'])
        {
            $this->modx->log(MODX_LOG_LEVEL_INFO, 'Attempting to remove/regenerate class files...');
            modxBuilder::deleteClassFiles($this->config["class_dir"], $verbose);
            modxBuilder::deleteClassFiles($this->config["mysql_class_dir"], $verbose);
        }

        // Use this to generate maps from your schema
        if ($this->config['regenerate_maps'])
        {
            if ($verbose)
            {
                $this->modx->log(MODX_LOG_LEVEL_INFO, 'Attempting to remove/regenerate map files...');
            }
            modxBuilder::deleteMapFiles($this->config["mysql_class_dir"], $verbose);
        }

        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $tend = $mtime;
        $totalTime = ($tend - $tstart);
        $totalTime = sprintf("%2.4f s", $totalTime);

        if ($verbose)
        {
            $this->modx->log(MODX_LOG_LEVEL_INFO, "Finished! Execution time: {$totalTime}");

            if ($this->config['regenerate_schema'])
            {
                $this->modx->log(MODX_LOG_LEVEL_INFO, "If you need to define aggregate/composite relationships in your XML schema file, be sure to regenerate your class files.");
            }
        }
    }

    public function parseSchema()
    {
        $this->modx->loadClass('transport.modPackageBuilder', '', false, true);
        $this->modx->setLogLevel(modX::LOG_LEVEL_INFO);
        $this->modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

        if (!is_dir($this->config['model_dir']))
        {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Model directory not found!');
            die();
        }
        if (!file_exists($this->config['xml_schema_file']))
        {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Schema file not found!');
            die();
        }
        $this->getGenerator()->parseSchema($this->config["xml_schema_file"], $this->config["model_dir"] . "/");

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Done!');
    }

    public function getPackageBulder(){
        if(!$this->builder){
            //Подгружаем класс для сборки пакетов
            $this->modx->loadClass('transport.modPackageBuilder', '', false, true);
            $this->builder = new modPackageBuilder($this->modx);
        }
        return $this->builder;
    }

    public function buildComponent()
    {
        $this->getPackageBulder();
        $this->builder->createPackage($this->config['package_name'], $this->config['package_version'], $this->config['package_release']);
        $this->builder->registerNamespace($this->config['package_name'], false, true, "{core_path}components/{$this->config['package_name']}/");

        //Затем начинаем процесс по упаковке и переносу
        //TODO сделать процесс упаковки и переноса c возможностью сборки из базы (база->файл->пакет)
    }

    /**
     * @param string $dir - a directory containing class files you wish to delete.
     * @param bool $verbose
     */
    public function deleteClassFiles($dir, $verbose = false)
    {
        $all_files = scandir($dir);
        foreach ($all_files as $f)
        {
            if (preg_match('#\.class\.php$#i', $f))
            {
                if (unlink("$dir/$f"))
                {
                    if ($verbose)
                    {
                        $this->modx->log(MODX_LOG_LEVEL_INFO, sprintf('Deleted file: %s/%s', $dir, $f));
                    }
                }
                else
                {
                    $this->modx->log(MODX_LOG_LEVEL_ERROR, sprintf('Failed to delete file: %s/%s', $dir, $f));
                }
            }
        }
    }

    /**
     * @param string $dir - a directory containing map files you wish to delete.
     * @param bool $verbose
     */
    public function deleteMapFiles($dir, $verbose = false)
    {
        $all_files = scandir($dir);
        foreach ($all_files as $f)
        {
            if (preg_match('#\.map\.inc\.php$#i', $f))
            {
                if (unlink("$dir/$f"))
                {
                    if ($verbose)
                    {
                        $this->modx->log(MODX_LOG_LEVEL_INFO, sprintf('Deleted file: %s/%s', $dir, $f));
                    }
                }
                else
                {
                    $this->modx->log(MODX_LOG_LEVEL_ERROR, sprintf('Failed to delete file: %s/%s', $dir, $f));
                }
            }
        }
    }
}