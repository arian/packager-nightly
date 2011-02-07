<?php

ini_set('display_errors', 1);

include dirname(__FILE__) . '/Builder.php';

$commit = null;
if (isset($_POST['payload'])){
	$data = json_decode($_POST['payload'], true);
	$commit = $data['after'];
}

Builder::$tmp = sys_get_temp_dir();

$builder = new Builder('mootools/mootools-core');
$builder->build('build/mootools-core.js', $commit);

// file_put_contents('build/log.txt', var_export(array($_GET, $_POST, $_SERVER), true));
