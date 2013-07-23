<?php
/**
 * snb\Provider
 * @package snb
 * @auther Masanori Nakashima
 */
namespace snb;
/**
 * snb\Provider
 * @package snb
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
