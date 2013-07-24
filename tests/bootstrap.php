<?php
define('DIR_PROJECT',dirname(dirname(__FILE__)));
define('DIR_LIBS',DIR_PROJECT.'/src/mychaelstyle');
define('DIR_TEST',dirname(__FILE__));
define('DIR_FIXTURES',DIR_TEST.'/fixtures');
define('DIR_TMP',DIR_TEST.'/tmp');
define('DIR_WORK',DIR_TEST.'/work');

set_include_path(get_include_path().':'.DIR_LIBS);

require_once(DIR_PROJECT."/vendor/autoload.php");

require_once DIR_TEST.'/mychaelstyle/TestBase.php';

if(!is_dir(DIR_TMP)){
  mkdir(DIR_TMP,0777,true);
}
if(!is_dir(DIR_WORK)){
  mkdir(DIR_WORK,0777,true);
}
