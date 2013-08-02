<?php
/**
 * mychaelstyle\db\Factory
 * @package mychaelstyle
 * @subpackage db
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\db;
require_once dirname(dirname(__FILE__)).'/ProviderFactory.php';
/**
 * mychaelstyle\db\Factory
 * @package mychaelstyle
 * @subpackage db
 * @auther Masanori Nakashima
 */
class Factory extends \mychaelstyle\ProviderFactory {
  /**
   * db providers' package name.
   */
  protected function getPackage(){
    return 'mychaelstyle\\db\\providers';
  }
  /**
   * db providers dir
   */
  protected function getPath(){
    return dirname(__FILE__).'/providers';
  }
}
