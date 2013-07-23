<?php
/**
 * mychaelstyle\Provider
 * @package mychaelstyle
 * @auther Masanori Nakashima
 */
namespace mychaelstyle;
/**
 * mychaelstyle\Provider
 * @package mychaelstyle
 * @auther Masanori Nakashima
 */
abstract class Provider {
  /**
   * connect
   * @param string $uri
   * @param array $options
   */
  abstract public function connect($uri,$options=array());
}
