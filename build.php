<?php

include dirname(__FILE__) . '/Builder.php';


Builder::$tmp = sys_get_temp_dir();


$builder = new Builder('mootools/mootools-core');
$builder->build('mootools-core.js');


