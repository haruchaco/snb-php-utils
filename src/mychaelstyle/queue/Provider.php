<?php
/**
 * mychaelstyle\queue\Provider
 * @package mychaelstyle
 * @subpackage queue
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\queue;
require_once dirname(dirname(__FILE__)).'/Provider.php';
/**
 * mychaelstyle\queue\Provider
 * @package mychaelstyle
 * @subpackage queue
 * @auther Masanori Nakashima
 */
abstract class Provider extends \mychaelstyle\Provider {
  abstract public function offer($body);
  abstract public function poll($callback=null);
  abstract public function peek($callback=null);
  abstract public function remove();
}
