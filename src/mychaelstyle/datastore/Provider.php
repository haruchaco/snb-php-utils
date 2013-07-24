<?php
/**
 * mychaelstyle\datastore\Provider
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\datastore;
require_once dirname(dirname(__FILE__)).'/Provider.php';
/**
 * mychaelstyle\datastore\Provider
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
abstract class Provider extends \mychaelstyle\Provider {
  abstract public function batchWrite(array $datas);
  abstract public function batchGet(array $keys);
  abstract public function batchRemove(array $keys);
  abstract public function write($table,array $data);
  abstract public function get($table,$key);
  abstract public function remove($table,$key);
}
