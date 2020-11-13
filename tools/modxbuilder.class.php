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
    public $modx;
    public $config = array();

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
        $this->modx->setPackage($this->config['package_name'], $this->config["model_dir"].'/');
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

    public function parseSchema($verbose = true)
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

    /**
     * @param modCategory $category
     * @param array $snippets
     * @param array $attr
     * @param bool $updateObject
     * @return bool
     */
    public function addSnippetsToCategory(&$category,$snippets,&$attr,$updateObject = true){
        $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Snippets'] = array(
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => $updateObject,
            xPDOTransport::UNIQUE_KEY => 'name',
        );
        return $category->addMany($snippets);
    }

    /**
     * @param modCategory $category
     * @param array modChunk[] $chunks
     * @param array $attr
     * @param bool $updateObject
     * @return bool
     */
    public function addChunksToCategory(&$category,$chunks,&$attr,$updateObject = true){
        $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Chunks'] = array(
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => $updateObject,
            xPDOTransport::UNIQUE_KEY => 'name',
        );
        return $category->addMany($chunks);
    }

    /**
     * @param modCategory $category
     * @param array modTemplate[] $templates
     * @param array $attr
     * @param bool $updateObject
     * @return bool
     */
    public function addTemplatesToCategory(&$category,$templates,&$attr,$updateObject = true){
        $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Templates'] = array(
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => $updateObject,
            xPDOTransport::UNIQUE_KEY => 'templatename',
        );
        return $category->addMany($templates);
    }

    /**
     * @param modCategory $category
     * @param array modPlugin[] $templates
     * @param array $attr
     * @param bool $updateObject
     * @return bool
     */
    public function addPluginsToCategory(&$category, $plugins, &$attr, $updateObject = true){
        $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Plugins'] = array(
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => $updateObject,
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array(
                'PluginEvents' => array(
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => $updateObject,
                    xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'],
                )
            )
        );
        return $category->addMany($plugins);
    }

    /**
     * @param modCategory $category
     * @param array modTemplateVar[] $tvs
     * @param array $attr
     * @param bool $updateObject
     * @return bool
     */
    public function addTVsToCategory(&$category, $tvs, &$attr, $updateObject = true){
        $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['TemplateVars'] = array(
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => $updateObject,
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array(
                'TemplateVarTemplates' => array(
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => $updateObject,
                    xPDOTransport::UNIQUE_KEY => ['tmplvarid','templateid'],
                )
            )
        );
        return $category->addMany($tvs);
    }

    /**
     * @param modTransportVehicle $vehicle
     * @param array $resolvers
     * @return int
     */
    public function addResolvers(&$vehicle,$resolvers){
        $cnt = 0;
        foreach($resolvers as $type => $resolver){
            if(isset($resolver['source']))
            {
                $cnt++;
                $vehicle->resolve($type,$resolver);
            }
            else{
                foreach($resolver as $resolverItem){
                    $cnt++;
                    $vehicle->resolve($type,$resolverItem);
                }
            }
        }
        return $cnt;
    }

    /**
     * @param modSystemSetting[] $settings
     * @param array $attr
     * @param bool $updateObject
     * @return bool
     */
    public function addSystemSettings($settings,$attr = array(), $updateObject = false){
        $noError = true;

        $sysSettingsAttr = array_merge(array(
            xPDOTransport::UNIQUE_KEY => 'key',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => $updateObject,
        ),$attr);
        foreach ($settings as $setting) {
            $vehicle = $this->builder->createVehicle($setting,$sysSettingsAttr);
            $noError = $noError && $this->builder->putVehicle($vehicle);
        }
        return $noError;
    }

    /**
     * @param modMenu[] $menus
     * @param array $attr
     * @param bool $updateObject
     * @return bool
     */
    public function addMenus($menus,$attr = array(), $updateObject = true){
        $noError = true;

        $menuSettingsArray = array_merge(array(
            xPDOTransport::UNIQUE_KEY => 'text',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => $updateObject,
        ),$attr);
        foreach ($menus as $menu) {
            $vehicle = $this->builder->createVehicle($menu,$menuSettingsArray);
            $noError = $noError && $this->builder->putVehicle($vehicle);
        }
        return $noError;
    }

    public function addPackageAttributes(){
        $attrs = array();
        if(file_exists($this->config['source_docs'] . 'changelog.txt')){
            $attrs['changelog'] = file_get_contents($this->config['source_docs'] . 'changelog.txt');
        }
        if(file_exists($this->config['source_docs'] . 'license.txt')){
            $attrs['license'] = file_get_contents($this->config['source_docs'] . 'license.txt');
        }
        if(file_exists($this->config['source_docs'] . 'readme.txt')){
            $attrs['readme'] = file_get_contents($this->config['source_docs'] . 'readme.txt');
        }
        if(file_exists($this->config['data'] . 'setup.options.php')){
            $attrs['setup-options'] = array('source' => $this->config['data'] . 'setup.options.php');
        }
        if(file_exists($this->config['data'] . 'setup.requires.php')){
            $requires = include $this->config['data'] . 'setup.requires.php';
            if(!empty($requires)){
                $attrs['requires'] = $requires;
            }
        }
        $this->builder->setPackageAttributes($attrs);
    }

    public function buildComponent()
    {
        $this->getPackageBulder();
        $this->builder->createPackage(str_replace(' ','',$this->config['real_package_name']), $this->config['package_version'], $this->config['package_release']);
        $namespace = $this->config['package_name'];
        $this->modx->log(xPDO::LOG_LEVEL_INFO,'Registering new namespace: '.$namespace);
        $this->builder->registerNamespace($this->config['package_name'], false, true, "{core_path}components/{$this->config['package_name']}/");

        // Create new category for chunks and snippets
        $categoryName = $this->config['real_package_name'];
        $this->modx->log(xPDO::LOG_LEVEL_INFO,'Creating new category: '.$categoryName);

        /** @var modCategory $category */
        $category= $this->modx->newObject('modCategory');
        $category->set('category',$this->config['real_package_name']);

        // Define attributes for category transport
        $categoryAttr = array(
            xPDOTransport::UNIQUE_KEY => 'category',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
        );

        //Define snippets
        /** @var modSnippet $snippets */
        $snippets = include $this->config['data'] . 'transport.snippets.php';
        if (!is_array($snippets)){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Are snippets empty? Skip them');
        }
        elseif($this->addSnippetsToCategory($category,$snippets,$categoryAttr)){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Added snippets: ' . count($snippets) . '.');
        }


        //Define chunks
        /** @var modChunk[] $chunks */
        $chunks = include $this->config['data'] . 'transport.chunks.php';
        if (!is_array($chunks)){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Are chunks empty? Skip them');
        }
        elseif($this->addChunksToCategory($category,$chunks,$categoryAttr)){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Added chunks: ' . count($chunks) . '.');
        }

        //Define templates
        /** @var modTemplate[] $templates */
        $templates = include $this->config['data'] . 'transport.templates.php';
        if (!is_array($templates)){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Are templates empty? Skip them');
        }
        elseif($this->addTemplatesToCategory($category,$templates,$categoryAttr)){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Added templates: ' . count($templates) . '.');
        }

        //Define tvs
        /** @var modTemplateVar[] $tvs */
        $tvs = include $this->config['data'] . 'transport.tvs.php';
        if (!is_array($tvs))
        {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Are template variables empty? Skip them');
        }
        elseif ($this->addTVsToCategory($category, $tvs, $categoryAttr, true))
        {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Added template variables: ' . count($tvs) . '.');
        }

        /*if (isset($templates) && is_array($templates))
        {
            foreach ($templates as $template)
            {
                //TODO add tvs to templates
            }
        }*/

        //Define plugins
        $plugins = include $this->config['data'] . 'transport.plugins.php';
        if (!is_array($plugins)){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Are plugins empty? Skip them');
        }
        elseif($this->addPluginsToCategory($category,$plugins,$categoryAttr)){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Added plugins: ' . count($plugins) . '.');
        }

        $vehicle = $this->builder->createVehicle($category,$categoryAttr);
        $this->builder->putVehicle($vehicle);

        //Define file resolvers
        $resolvers = include $this->config['resolvers'] . 'resolvers.php';
        if(!is_array($resolvers)){
            $this->modx->log(modX::LOG_LEVEL_INFO,'Are file resolvers empty? Skip them');
        }
        else{
            $count = $this->addResolvers($vehicle,$resolvers);
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Added resolvers: ' . $count . '.');
        }
        
        $this->builder->putVehicle($vehicle);

        //Define system settings
        $settings = include $this->config['data'].'transport.settings.php';
        if (!is_array($settings)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,'Are system settings empty? Skip them');
        } else {
            $this->addSystemSettings($settings);
            $this->modx->log(modX::LOG_LEVEL_INFO,'Added system settings: '.count($settings).'.');
        }

        //Define menu items
        $menus = include $this->config['data'].'transport.menu.php';
        if (!is_array($menus)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,'Are menu items empty? Skip them');
        } else {
            $this->addMenus($menus);
            $this->modx->log(modX::LOG_LEVEL_INFO,'Added menu items: '.count($menus).'.');
        }

        $this->addPackageAttributes();
        $this->modx->log(modX::LOG_LEVEL_INFO,'Added package attributes!');

        //Start packing into zip
        $this->modx->log(modX::LOG_LEVEL_INFO,'Start packing into zip');

        if($this->builder->pack()){
            $this->modx->log(modX::LOG_LEVEL_INFO,'Packet was successfully packed!');
        }
        else{
            $this->modx->log(modX::LOG_LEVEL_ERROR,'Something went wrong... Error while packing into zip');
        }
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