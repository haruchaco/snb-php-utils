<?php
/**
 * snb\queue\Provider
 * @package snb
 * @subpackage queue
 * @auther Masanori Nakashima
 */
namespace snb\queue;
require_once dirname(dirname(__FILE__)).'/Provider.php';
/**
 * snb\queue\Provider
 * @package snb
 * @subpackage queue
 * @auther Masanori Nakashima
 */
abstract class Provider extends \snb\Provider {
  abstract public function offer($body);
  abstract public function poll($callback=null);
  abstract public function peek($callback=null);
  abstract public function remove();
}
