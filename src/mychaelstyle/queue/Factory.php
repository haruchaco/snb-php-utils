<?php
/**
 * mychaelstyle\queue\Factory
 * @package mychaelstyle
 * @subpackage queue
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\queue;
require_once dirname(dirname(__FILE__)).'/ProviderFactory.php';
/**
 * mychaelstyle\queue\Factory
 * @package mychaelstyle
 * @subpackage queue
 * @auther Masanori Nakashima
 */
class Factory extends \mychaelstyle\ProviderFactory {
  /**
   * queue providers' package name.
   */
  protected function getPackage(){
    return 'mychaelstyle\\queue\\providers';
  }
  /**
   * queue providers dir
   */
  protected function getPath(){
    return dirname(__FILE__).'/providers';
  }
}
