<?php
$projectRoot = dirname(dirname(__FILE__));
$projectLibs = $projectRoot.'/libs';
set_include_path(get_include_path().':'.$projectLibs);
define('DIR_TEST',dirname(__FILE__));

require_once DIR_TEST.'/TestBase.php';

if(!is_dir(DIR_TEST.'/tmp')){
  mkdir(DIR_TEST.'/tmp',0777,true);
}
if(!is_dir(DIR_TEST.'/work')){
  mkdir(DIR_TEST.'/work',0777,true);
}
