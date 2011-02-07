<?php

ini_set('display_errors', 1);

include dirname(__FILE__) . '/Builder.php';

$commit = null;
$repository = null;
$repositories = array('mootools-core', 'mootools-more');

if (isset($_POST['payload'])){
	$data = json_decode($_POST['payload'], true);
	$commit = $data['after'];
	if (!preg_match('/^([0-9a-f]+)$/', $commit)) $commit = null;
	if (isset($data['repository']) && isset($data['repository']['name'])){
		$repository = $data['repository']['name'];
		if (!in_array($repository, $repositories)) $repository = null;
	}
}

if (!$repository) $repository = $repositories[0];


Builder::$tmp = sys_get_temp_dir();

$builder = new Builder('mootools/' . $repository);
$builder->build('build/' . $repository . '.js', $commit);


$log =  'commit: '. $commit . PHP_EOL
	. 'repository: ' . $repository . PHP_EOL
	. var_export(array($_POST, $_SERVER), true);

file_put_contents('build/log.txt', $log);
