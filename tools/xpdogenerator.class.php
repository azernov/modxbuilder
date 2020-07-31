<?php
/**
 * Класс переопределяющий несколько методов стандартного mysql генератора
 */
class xPDOGenerator_my extends xPDOGenerator_mysql {

    private $classPrefix = '';
    private $restrictPrefix = false;
    private $tablePrefix = '';

    public $classTemplate = <<<EOD
<?php
/**
[+phpdoc-vars+]
 */
class [+class+] extends [+extends+] {}
EOD;


    public function setClassPrefix($string)
    {
        $this->classPrefix = $string;
    }

    /**
     * Gets a class name from a table name by splitting the string by _ and
     * capitalizing each token.
     *
     * Переопределяет родительскую функцию и добавляет префикс к названию класса
     *
     * @access public
     * @param string $string The table name to format.
     * @return string The formatted string.
     */
    public function getClassName($string) {
        if (is_string($string) && $strArray= explode('_', $string)) {
            $return= '';
            while (list($k, $v)= each($strArray)) {
                //Если мы обрезаем табличный префикс, то первый кусочек пропускаем
                if($this->restrictPrefix && $k==0) continue;
                $return.= strtoupper(substr($v, 0, 1)) . substr($v, 1) . '';
            }
            $string= $return;
        }
        return $this->classPrefix.trim($string);
    }

    /**
     * Write an xPDO XML Schema from your database.
     * Переписан механизм формирования имени класса.
     *
     * @param string $schemaFile The name (including path) of the schemaFile you
     * want to write.
     * @param string $package Name of the package to generate the classes in.
     * @param string $baseClass The class which all classes in the package will
     * extend; by default this is set to {@link xPDOObject} and any
     * auto_increment fields with the column name 'id' will extend {@link
     * xPDOSimpleObject} automatically.
     * @param string $tablePrefix The table prefix for the current connection,
     * which will be removed from all of the generated class and table names.
     * Specify a prefix when creating a new {@link xPDO} instance to recreate
     * the tables with the same prefix, but still use the generic class names.
     * @param boolean $restrictPrefix Only reverse-engineer tables that have the
     * specified tablePrefix; if tablePrefix is empty, this is ignored.
     * @return boolean True on success, false on failure.
     */
    public function writeSchema($schemaFile, $package= '', $baseClass= '', $tablePrefix= '', $restrictPrefix= false, $tablePrefixForSchema = '') {
        if (empty ($package))
            $package= $this->manager->xpdo->package;
        if (empty ($baseClass))
            $baseClass= 'xPDOObject';
        if (empty ($tablePrefix) || $tablePrefix == '')
            $tablePrefix= $this->manager->xpdo->config[xPDO::OPT_TABLE_PREFIX];
        $this->tablePrefix = $tablePrefixForSchema;
        $this->restrictPrefix = $restrictPrefix;
        $tablePrefix .= $tablePrefixForSchema;
        $schemaVersion = xPDO::SCHEMA_VERSION;
        $xmlContent = array();
        $xmlContent[] = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $xmlContent[] = "<model package=\"{$package}\" baseClass=\"{$baseClass}\" platform=\"mysql\" defaultEngine=\"MyISAM\" version=\"{$schemaVersion}\">";
        //read list of tables
        $dbname= $this->manager->xpdo->escape($this->manager->xpdo->config['dbname']);
        $tablePrefixEscaped = str_replace('_','\_', $tablePrefix);
        $tableLike= ($tablePrefix && $restrictPrefix) ? " LIKE '{$tablePrefixEscaped}%'" : '';
        $tablesStmt= $this->manager->xpdo->prepare("SHOW TABLES FROM {$dbname}{$tableLike}");
        $tstart = microtime(true);
        $tablesStmt->execute();
        $this->manager->xpdo->queryTime += microtime(true) - $tstart;
        $this->manager->xpdo->executedQueries++;
        $tables= $tablesStmt->fetchAll(PDO::FETCH_NUM);
        if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, print_r($tables, true));
        foreach ($tables as $table) {
            $xmlObject= array();
            $xmlFields= array();
            $xmlIndices= array();
            $tmpPrefix = str_replace($tablePrefixForSchema,'',$tablePrefix);
            if (!$tableName= $this->getTableName($table[0], $tmpPrefix, false)) {
                continue;
            }

            //Здесь мы немного заменим принцип формирования имени класса

            //Сначала получим то, что лежит в комментариях к таблице
            $infoStmt = $this->manager->xpdo->query("SHOW TABLE STATUS FROM {$dbname} WHERE Name = '" . $table[0] . "'");
            $comment = '';
            if($infoStmt)
            {
                $info = $infoStmt->fetchAll(PDO::FETCH_ASSOC);
                if(!empty($info))
                {
                    $comment = $info[0]['Comment'];
                }
            }

            //Если названия класса нет в комментариях к таблице, то формируем его по общему прицнипу
            $class = trim($comment) == '' ? $this->getClassName($tableName) : $comment;

            $extends= $baseClass;
            $fieldsStmt= $this->manager->xpdo->query('SHOW COLUMNS FROM ' . $this->manager->xpdo->escape($table[0]));
            if ($fieldsStmt) {
                $fields= $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);
                if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, print_r($fields, true));
                if (!empty($fields)) {
                    foreach ($fields as $field) {
                        $Field= '';
                        $Type= '';
                        $Null= '';
                        $Key= '';
                        $Default= '';
                        $Extra= '';
                        extract($field, EXTR_OVERWRITE);
                        $Type= xPDO :: escSplit(' ', $Type, "'", 2);
                        $precisionPos= strpos($Type[0], '(');
                        $dbType= $precisionPos? substr($Type[0], 0, $precisionPos): $Type[0];
                        $dbType= strtolower($dbType);
                        $Precision= $precisionPos? substr($Type[0], $precisionPos + 1, strrpos($Type[0], ')') - ($precisionPos + 1)): '';
                        if (!empty ($Precision)) {
                            $Precision= ' precision="' . trim($Precision) . '"';
                        }
                        $attributes= '';
                        if (isset ($Type[1]) && !empty ($Type[1])) {
                            $attributes= ' attributes="' . trim($Type[1]) . '"';
                        }
                        $PhpType= $this->manager->xpdo->driver->getPhpType($dbType);
                        $Null= ' null="' . (($Null === 'NO') ? 'false' : 'true') . '"';
                        $Key= $this->getIndex($Key);
                        $Default= $this->getDefault($Default);
                        if (!empty ($Extra)) {
                            if ($Extra === 'auto_increment') {
                                if ($baseClass === 'xPDOObject' && $Field === 'id') {
                                    $extends= 'xPDOSimpleObject';
                                    continue;
                                } else {
                                    $Extra= ' generated="native"';
                                }
                            } else {
                                $Extra= ' extra="' . strtolower($Extra) . '"';
                            }
                            $Extra= ' ' . $Extra;
                        }
                        $xmlFields[] = "\t\t<field key=\"{$Field}\" dbtype=\"{$dbType}\"{$Precision}{$attributes} phptype=\"{$PhpType}\"{$Null}{$Default}{$Key}{$Extra} />";
                    }
                } else {
                    $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'No columns were found in table ' .  $table[0]);
                }
            } else {
                $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Error retrieving columns for table ' .  $table[0]);
            }
            $whereClause= ($extends === 'xPDOSimpleObject' ? " WHERE `Key_name` != 'PRIMARY'" : '');
            $indexesStmt= $this->manager->xpdo->query('SHOW INDEXES FROM ' . $this->manager->xpdo->escape($table[0]) . $whereClause);
            if ($indexesStmt) {
                $indexes= $indexesStmt->fetchAll(PDO::FETCH_ASSOC);
                if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Indices for table {$table[0]}: " . print_r($indexes, true));
                if (!empty($indexes)) {
                    $indices = array();
                    foreach ($indexes as $index) {
                        if (!array_key_exists($index['Key_name'], $indices)) $indices[$index['Key_name']] = array();
                        $indices[$index['Key_name']][$index['Seq_in_index']] = $index;
                    }
                    foreach ($indices as $index) {
                        $xmlIndexCols = array();
                        if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Details of index: " . print_r($index, true));
                        foreach ($index as $columnSeq => $column) {
                            if ($columnSeq == 1) {
                                $keyName = $column['Key_name'];
                                $primary = $keyName == 'PRIMARY' ? 'true' : 'false';
                                $unique = empty($column['Non_unique']) ? 'true' : 'false';
                                $packed = empty($column['Packed']) ? 'false' : 'true';
                                $type = $column['Index_type'];
                            }
                            $null = $column['Null'] == 'YES' ? 'true' : 'false';
                            $xmlIndexCols[]= "\t\t\t<column key=\"{$column['Column_name']}\" length=\"{$column['Sub_part']}\" collation=\"{$column['Collation']}\" null=\"{$null}\" />";
                        }
                        $xmlIndices[]= "\t\t<index alias=\"{$keyName}\" name=\"{$keyName}\" primary=\"{$primary}\" unique=\"{$unique}\" type=\"{$type}\" >";
                        $xmlIndices[]= implode("\n", $xmlIndexCols);
                        $xmlIndices[]= "\t\t</index>";
                    }
                } else {
                    $this->manager->xpdo->log(xPDO::LOG_LEVEL_WARN, 'No indexes were found in table ' .  $table[0]);
                }
            } else {
                $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Error getting indexes for table ' .  $table[0]);
            }
            $xmlObject[] = "\t<object class=\"{$class}\" table=\"{$tableName}\" extends=\"{$extends}\">";
            $xmlObject[] = implode("\n", $xmlFields);
            if (!empty($xmlIndices)) {
                $xmlObject[] = '';
                $xmlObject[] = implode("\n", $xmlIndices);
            }
            $xmlObject[] = "\t</object>";
            $xmlContent[] = implode("\n", $xmlObject);
        }
        $xmlContent[] = "</model>";
        if ($this->manager->xpdo->getDebug() === true) {
            $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, implode("\n", $xmlContent));
        }
        $file= fopen($schemaFile, 'wb');
        $written= fwrite($file, implode("\n", $xmlContent));
        fclose($file);
        return true;
    }

    /**
     * Parses an XPDO XML schema and generates classes and map files from it.
     *
     * Requires SimpleXML for parsing an XML schema.
     *
     * @param string $schemaFile The name of the XML file representing the
     * schema.
     * @param string $outputDir The directory in which to generate the class and
     * map files into.
     * @param boolean $compile Create compiled copies of the classes and maps from the schema.
     * @return boolean True on success, false on failure.
     */
    public function parseSchema($schemaFile, $outputDir= '', $compile= false) {
        $this->schemaFile= $schemaFile;
        $this->classTemplate= $this->getClassTemplate();
        if (!is_file($schemaFile)) {
            $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not find specified XML schema file {$schemaFile}");
            return false;
        }

        $this->schema = new SimpleXMLElement($schemaFile, 0, true);
        if (isset($this->schema)) {
            foreach ($this->schema->attributes() as $attributeKey => $attribute) {
                /** @var SimpleXMLElement $attribute */
                $this->model[$attributeKey] = (string) $attribute;
            }
            if (isset($this->schema->object)) {
                foreach ($this->schema->object as $object) {
                    /** @var SimpleXMLElement $object */
                    $class = (string) $object['class'];
                    $extends = isset($object['extends']) ? (string) $object['extends'] : $this->model['baseClass'];
                    $this->classes[$class] = array('extends' => $extends);
                    $this->map[$class] = array(
                        'package' => $this->model['package'],
                        'version' => $this->model['version']
                    );
                    foreach ($object->attributes() as $objAttrKey => $objAttr) {
                        if ($objAttrKey == 'class') continue;
                        $this->map[$class][$objAttrKey]= (string) $objAttr;
                    }

                    $engine = (string) $object['engine'];
                    if (!empty($engine)) {
                        $this->map[$class]['tableMeta'] = array('engine' => $engine);
                    }

                    $this->map[$class]['fields']= array();
                    $this->map[$class]['fieldMeta']= array();
                    if (isset($object->field)) {
                        foreach ($object->field as $field) {
                            $key = (string) $field['key'];
                            $dbtype = (string) $field['dbtype'];
                            $defaultType = $this->manager->xpdo->driver->getPhpType($dbtype);
                            $this->map[$class]['fields'][$key]= null;
                            $this->map[$class]['fieldMeta'][$key]= array();
                            foreach ($field->attributes() as $fldAttrKey => $fldAttr) {
                                $fldAttrValue = (string) $fldAttr;
                                switch ($fldAttrKey) {
                                    case 'key':
                                        continue 2;
                                    case 'default':
                                        if ($fldAttrValue === 'NULL') {
                                            $fldAttrValue = null;
                                        }
                                        switch ($defaultType) {
                                            case 'integer':
                                            case 'boolean':
                                            case 'bit':
                                                $fldAttrValue = (integer) $fldAttrValue;
                                                break;
                                            case 'float':
                                            case 'numeric':
                                                $fldAttrValue = (float) $fldAttrValue;
                                                break;
                                            default:
                                                break;
                                        }
                                        $this->map[$class]['fields'][$key]= $fldAttrValue;
                                        break;
                                    case 'null':
                                        $fldAttrValue = (!empty($fldAttrValue) && strtolower($fldAttrValue) !== 'false') ? true : false;
                                        break;
                                    default:
                                        break;
                                }
                                $this->map[$class]['fieldMeta'][$key][$fldAttrKey]= $fldAttrValue;
                            }
                        }
                    }
                    if (isset($object->alias)) {
                        $this->map[$class]['fieldAliases'] = array();
                        foreach ($object->alias as $alias) {
                            $aliasKey = (string) $alias['key'];
                            $aliasNode = array();
                            foreach ($alias->attributes() as $attrName => $attr) {
                                $attrValue = (string) $attr;
                                switch ($attrName) {
                                    case 'key':
                                        continue 2;
                                    case 'field':
                                        $aliasNode = $attrValue;
                                        break;
                                    default:
                                        break;
                                }
                            }
                            if (!empty($aliasKey) && !empty($aliasNode)) {
                                $this->map[$class]['fieldAliases'][$aliasKey] = $aliasNode;
                            }
                        }
                    }
                    if (isset($object->index)) {
                        $this->map[$class]['indexes'] = array();
                        foreach ($object->index as $index) {
                            $indexNode = array();
                            $indexName = (string) $index['name'];
                            foreach ($index->attributes() as $attrName => $attr) {
                                $attrValue = (string) $attr;
                                switch ($attrName) {
                                    case 'name':
                                        continue 2;
                                    case 'primary':
                                    case 'unique':
                                    case 'fulltext':
                                        $attrValue = (empty($attrValue) || $attrValue === 'false' ? false : true);
                                    default:
                                        $indexNode[$attrName] = $attrValue;
                                        break;
                                }
                            }
                            if (!empty($indexNode) && isset($index->column)) {
                                $indexNode['columns']= array();
                                foreach ($index->column as $column) {
                                    $columnKey = (string) $column['key'];
                                    $indexNode['columns'][$columnKey] = array();
                                    foreach ($column->attributes() as $attrName => $attr) {
                                        $attrValue = (string) $attr;
                                        switch ($attrName) {
                                            case 'key':
                                                continue 2;
                                            case 'null':
                                                $attrValue = (empty($attrValue) || $attrValue === 'false' ? false : true);
                                            default:
                                                $indexNode['columns'][$columnKey][$attrName]= $attrValue;
                                                break;
                                        }
                                    }
                                }
                                if (!empty($indexNode['columns'])) {
                                    $this->map[$class]['indexes'][$indexName]= $indexNode;
                                }
                            }
                        }
                    }
                    if (isset($object->composite)) {
                        $this->map[$class]['composites'] = array();
                        foreach ($object->composite as $composite) {
                            $compositeNode = array();
                            $compositeAlias = (string) $composite['alias'];
                            foreach ($composite->attributes() as $attrName => $attr) {
                                $attrValue = (string) $attr;
                                switch ($attrName) {
                                    case 'alias' :
                                        continue 2;
                                    case 'criteria' :
                                        $attrValue = $this->manager->xpdo->fromJSON(urldecode($attrValue));
                                    default :
                                        $compositeNode[$attrName]= $attrValue;
                                        break;
                                }
                            }
                            if (!empty($compositeNode)) {
                                if (isset($composite->criteria)) {
                                    /** @var SimpleXMLElement $criteria */
                                    foreach ($composite->criteria as $criteria) {
                                        $criteriaTarget = (string) $criteria['target'];
                                        $expression = (string) $criteria;
                                        if (!empty($expression)) {
                                            $expression = $this->manager->xpdo->fromJSON($expression);
                                            if (!empty($expression)) {
                                                if (!isset($compositeNode['criteria'])) $compositeNode['criteria'] = array();
                                                if (!isset($compositeNode['criteria'][$criteriaTarget])) $compositeNode['criteria'][$criteriaTarget] = array();
                                                $compositeNode['criteria'][$criteriaTarget] = array_merge($compositeNode['criteria'][$criteriaTarget], (array) $expression);
                                            }
                                        }
                                    }
                                }
                                $this->map[$class]['composites'][$compositeAlias] = $compositeNode;
                            }
                        }
                    }
                    if (isset($object->aggregate)) {
                        $this->map[$class]['aggregates'] = array();
                        foreach ($object->aggregate as $aggregate) {
                            $aggregateNode = array();
                            $aggregateAlias = (string) $aggregate['alias'];
                            foreach ($aggregate->attributes() as $attrName => $attr) {
                                $attrValue = (string) $attr;
                                switch ($attrName) {
                                    case 'alias' :
                                        continue 2;
                                    case 'criteria' :
                                        $attrValue = $this->manager->xpdo->fromJSON(urldecode($attrValue));
                                    default :
                                        $aggregateNode[$attrName]= $attrValue;
                                        break;
                                }
                            }
                            if (!empty($aggregateNode)) {
                                if (isset($aggregate->criteria)) {
                                    /** @var SimpleXMLElement $criteria */
                                    foreach ($aggregate->criteria as $criteria) {
                                        $criteriaTarget = (string) $criteria['target'];
                                        $expression = (string) $criteria;
                                        if (!empty($expression)) {
                                            $expression = $this->manager->xpdo->fromJSON($expression);
                                            if (!empty($expression)) {
                                                if (!isset($aggregateNode['criteria'])) $aggregateNode['criteria'] = array();
                                                if (!isset($aggregateNode['criteria'][$criteriaTarget])) $aggregateNode['criteria'][$criteriaTarget] = array();
                                                $aggregateNode['criteria'][$criteriaTarget] = array_merge($aggregateNode['criteria'][$criteriaTarget], (array) $expression);
                                            }
                                        }
                                    }
                                }
                                $this->map[$class]['aggregates'][$aggregateAlias] = $aggregateNode;
                            }
                        }
                    }
                    if (isset($object->validation)) {
                        $this->map[$class]['validation'] = array();
                        $validation = $object->validation[0];
                        $validationNode = array();
                        foreach ($validation->attributes() as $attrName => $attr) {
                            $validationNode[$attrName]= (string) $attr;
                        }
                        if (isset($validation->rule)) {
                            $validationNode['rules'] = array();
                            foreach ($validation->rule as $rule) {
                                $ruleNode = array();
                                $field= (string) $rule['field'];
                                $name= (string) $rule['name'];
                                foreach ($rule->attributes() as $attrName => $attr) {
                                    $attrValue = (string) $attr;
                                    switch ($attrName) {
                                        case 'field' :
                                        case 'name' :
                                            continue 2;
                                        default :
                                            $ruleNode[$attrName]= $attrValue;
                                            break;
                                    }
                                }
                                if (!empty($field) && !empty($name) && !empty($ruleNode)) {
                                    $validationNode['rules'][$field][$name]= $ruleNode;
                                }
                            }
                            if (!empty($validationNode['rules'])) {
                                $this->map[$class]['validation'] = $validationNode;
                            }
                        }
                    }
                }
            } else {
                $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Schema {$schemaFile} contains no valid object elements.");
            }
        } else {
            $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not read schema from {$schemaFile}.");
        }

        $om_path= XPDO_CORE_PATH . 'om/';
        $path= !empty ($outputDir) ? $outputDir : $om_path;
        if (isset ($this->model['package']) && strlen($this->model['package']) > 0) {
            $path .= strtr($this->model['package'], '.', '/');
            $path .= '/';
        }
        $this->outputMeta($path);
        $this->outputMaps($path);
        $this->outputClasses($path);

        if ($compile) $this->compile($path, $this->model, $this->classes, $this->maps);
        unset($this->model, $this->classes, $this->map);
        return true;
    }

    /**
     * Write the generated class files to the specified path.
     *
     * @access public
     * @param string $path An absolute path to write the generated class files
     * to.
     */
    public function outputClasses($path) {
        $newClassGeneration= false;
        $newPlatformGeneration= false;
        $platform= $this->model['platform'];
        if (!is_dir($path)) {
            $newClassGeneration= true;
            if ($this->manager->xpdo->getCacheManager()) {
                if (!$this->manager->xpdo->cacheManager->writeTree($path)) {
                    $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not create model directory at {$path}");
                    return false;
                }
            }
        }
        $ppath= $path;
        $ppath .= $platform;
        if (!is_dir($ppath)) {
            $newPlatformGeneration= true;
            if ($this->manager->xpdo->getCacheManager()) {
                if (!$this->manager->xpdo->cacheManager->writeTree($ppath)) {
                    $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not create platform subdirectory {$ppath}");
                    return false;
                }
            }
        }
        $model= $this->model;
        if (isset($this->model['phpdoc-package'])) {
            $model['phpdoc-package']= '@package ' . $this->model['phpdoc-package'];
            if (isset($this->model['phpdoc-subpackage']) && !empty($this->model['phpdoc-subpackage'])) {
                $model['phpdoc-subpackage']= '@subpackage ' . $this->model['phpdoc-subpackage'] . '.' . $this->model['platform'];
            } else {
                $model['phpdoc-subpackage']= '@subpackage ' . $this->model['platform'];
            }
        } else {
            $basePos= strpos($this->model['package'], '.');
            $package= $basePos
                ? substr($this->model['package'], 0, $basePos)
                : $this->model['package'];
            $subpackage= $basePos
                ? substr($this->model['package'], $basePos + 1)
                : '';
            $model['phpdoc-package']= '@package ' . $package;
            if ($subpackage) $model['phpdoc-subpackage']= '@subpackage ' . $subpackage;
        }



        foreach ($this->classes as $className => $classDef) {
            /*echo '<pre>';
            var_dump($this->map[$className]);
            echo '</pre>';*/
            $newClass= false;
            $classDef['class']= $className;
            $classDef['class-lowercase']= strtolower($className);

            $classDef['phpdoc-vars'] = $this->generateClassPhpDoc($this->map[$className]);

            $classDef= array_merge($model, $classDef);
            $replaceVars= array ();
            foreach ($classDef as $varKey => $varValue) {
                if (is_scalar($varValue)) $replaceVars["[+{$varKey}+]"]= $varValue;
            }




            $fileContent= str_replace(array_keys($replaceVars), array_values($replaceVars), $this->classTemplate);
            if (is_dir($path)) {
                $fileName= $path . strtolower($className) . '.class.php';
                if (!file_exists($fileName)) {
                    if ($file= @ fopen($fileName, 'wb')) {
                        if (!fwrite($file, $fileContent)) {
                            $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not write to file: {$fileName}");
                        }
                        $newClass= true;
                        @fclose($file);
                    } else {
                        $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not open or create file: {$fileName}");
                    }
                } else {
                    $newClass= false;
                    $this->manager->xpdo->log(xPDO::LOG_LEVEL_INFO, "Skipping {$fileName}; file already exists.\nMove existing class files to regenerate them.");
                }
            } else {
                $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not open or create dir: {$path}");
            }
            $fileContent= str_replace(array_keys($replaceVars), array_values($replaceVars), $this->getClassPlatformTemplate($platform));
            if (is_dir($ppath)) {
                $fileName= $ppath . '/' . strtolower($className) . '.class.php';
                if (!file_exists($fileName)) {
                    if ($file= @ fopen($fileName, 'wb')) {
                        if (!fwrite($file, $fileContent)) {
                            $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not write to file: {$fileName}");
                        }
                        @fclose($file);
                    } else {
                        $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not open or create file: {$fileName}");
                    }
                } else {
                    $this->manager->xpdo->log(xPDO::LOG_LEVEL_INFO, "Skipping {$fileName}; file already exists.\nMove existing class files to regenerate them.");
                    if ($newClassGeneration || $newClass) $this->manager->xpdo->log(xPDO::LOG_LEVEL_WARN, "IMPORTANT: {$fileName} already exists but you appear to have generated classes with an older xPDO version.  You need to edit your class definition in this file to extend {$className} rather than {$classDef['extends']}.");
                }
            } else {
                $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not open or create dir: {$path}");
            }
        }
    }

    /**
     * Generate phpdoc for model class
     * @param $classMap
     * @return string
     */
    public function generateClassPhpDoc($classMap)
    {
        $doc = '';
        $doc .= " * @package ".$classMap['package']."\n";
        foreach($classMap['fieldMeta'] as $fieldName => $fieldData)
        {
            $doc .= " * @property ".$fieldData['phptype']." $".$fieldName."\n";
        }

        if(isset($classMap['composites']))
        {
            foreach($classMap['composites'] as $fieldName => $fieldData)
            {
                $brackets = $fieldData['cardinality'] == 'many' ? '[]' : '';
                $doc .= " * @property ".$fieldData['class'].$brackets." $".$fieldName."\n";
            }
        }

        if(isset($classMap['aggregates']))
        {
            foreach($classMap['aggregates'] as $fieldName => $fieldData)
            {
                $brackets = $fieldData['cardinality'] == 'many' ? '[]' : '';
                $doc .= " * @property ".$fieldData['class'].$brackets." $".$fieldName."\n";
            }
        }
        $doc = trim($doc,"\n");
        return $doc;
    }
}
