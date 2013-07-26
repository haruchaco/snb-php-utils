<?php
/**
 * mychaelstyle\storage\Factory
 * @package mychaelstyle
 * @subpackage storage
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\storage;
require_once dirname(dirname(__FILE__)).'/ProviderFactory.php';
/**
 * mychaelstyle\storage\Factory
 * @package mychaelstyle
 * @subpackage storage
 * @auther Masanori Nakashima
 */
class Factory extends \mychaelstyle\ProviderFactory {
  /**
   * storage providers' package name.
   */
  protected function getPackage(){
    return 'mychaelstyle\\storage\\providers';
  }
  /**
   * storage providers dir
   */
  protected function getPath(){
    return dirname(__FILE__).'/providers';
  }
}
