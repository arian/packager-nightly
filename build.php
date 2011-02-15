<?php

ini_set('display_errors', 1);

include dirname(__FILE__) . '/Builder.php';

$commit = null;
$repositories = array(
	'mootools-core' => array(
		'branch' => 'master'
	),
	'mootools-more' => array(
		'branch' => 'master'
	)
);
$defaultRepository = 'mootools-core';
$repository = $defaultRepository;
$branch = $repositories[$repository]['branch'];

if (isset($_POST['payload'])){
	$data = json_decode($_POST['payload'], true);
	$commit = $data['after'];
	if (!preg_match('/^([0-9a-f]+)$/', $commit)) $commit = null;
	if (isset($data['repository']) && isset($data['repository']['name'])){
		$repository = $data['repository']['name'];
		if (empty($repositories[$repository])) $repository = null;
	}

	if (!$repository) $repository = $defaultRepository;
	$branch = $repositories[$repository]['branch'];
	if ($data['ref'] != 'refs/heads/' . $branch) $branch = false;
}

if ($branch){
	Builder::$tmp = sys_get_temp_dir();

	$builder = new Builder('mootools/' . $repository, $branch);
	$builder->build('build/' . $repository . '.js', $commit);
}

$log =  'commit: '. $commit . PHP_EOL
	. 'repository: ' . $repository . PHP_EOL
	. var_export(array($_POST, $_SERVER), true);

file_put_contents('build/log.txt', $log);
