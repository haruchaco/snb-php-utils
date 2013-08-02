<?php
/**
 * mychaelstyle\db\providers\Mysql
 * @package mychaelstyle
 * @subpackage db
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\db\providers;
require_once dirname(dirname(dirname(__FILE__))).'/ProviderMysql.php';
require_once dirname(dirname(__FILE__)).'/Provider.php';
/**
 * mychaelstyle\db\providers\Mysql
 * @package mychaelstyle
 * @subpackage db
 * @auther Masanori Nakashima
 */
class Mysql extends \mychaelstyle\ProviderMysql implements \mychaelstyle\db\Provider {
   /**
   * constructor
   */
  public function __construct(){
    parent::__construct();
  }
}
