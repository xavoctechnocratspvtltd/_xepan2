<?php

$profiler_time = microtime(true); 
require_once '../vendor/autoload.php';

if (file_exists('../agiletoolkit-sandbox.phar')) {
    require_once "../agiletoolkit-sandbox.phar";
}

require_once 'lib/Install.php';

$api = new Install('install');
$api->main();

if($api->getConfig('profiler',false)!==false) {
	if(is_bool($api->getConfig('profiler',false)) || $api->getConfig('profiler',false) === $api->current_website_name){
		$api->profiler->mark('finish');
		$api->profiler->dump();
	}
}