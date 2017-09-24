<?php
/**
 * @var modxBuilder $this
 */

$snippets = array();
/* @avr modSnippet $snippet */
$snippet = $this->modx->newObject('modSnippet');

$snippetContent = 'return date("Y-m-d");';
//Удаляем <?php теги
preg_match('#\<\?php(.*)#is', $snippetContent, $data);
$snippetContent = $data[1];

$snippet->fromArray(array(
    'id' => 0,
    'name' => 'testSnippet',
    'description' => 'test description',
    'snippet' => $snippetContent,
    'static' => 0,
    'source' => 1,
    'static_file' => "core/components/{$this->config['package_name']}/elements/snippets/testSnippet.php",
), '', true, true);

$snippet->setProperties(array(
    array(
        'name' => 'test_snippet_property1',
        'desc' => 'mycmp_prop_test_snippet_property_1',
        'lexicon' => 'mycmp:default',
    )
));
$snippets[] = $snippet;
unset($snippet,$snippetContent);
return $snippets;