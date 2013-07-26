<?php
/**
 * mychaelstyle\Provider
 * @package mychaelstyle
 * @auther Masanori Nakashima
 */
namespace mychaelstyle;
require_once dirname(__FILE__).'/Exception.php';
/**
 * mychaelstyle\Provider
 * @package mychaelstyle
 * @auther Masanori Nakashima
 */
interface Provider {
  /**
   * connect
   * @param string $uri
   * @param array $options
   */
  public function connect($uri,$options=array());
  /**
   * disconnect
   */
  public function disconnect();
}
