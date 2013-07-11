<?php
$projectRoot = dirname(dirname(__FILE__));
$projectLibs = $projectRoot.'/libs';
set_include_path(get_include_path().':'.$projectLibs);
define('DIR_TEST',dirname(__FILE__));

