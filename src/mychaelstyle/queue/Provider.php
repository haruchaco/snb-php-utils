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
interface Provider extends \mychaelstyle\Provider {
  public function offer($body);
  public function poll($callback=null);
  public function peek($callback=null);
  public function remove();
}
