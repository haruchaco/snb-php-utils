<?php
/**
 * mychaelstyle\datastore\Factory
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\datastore;
require_once dirname(dirname(__FILE__)).'/ProviderFactory.php';
/**
 * mychaelstyle\datastore\Factory
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
class Factory extends \mychaelstyle\ProviderFactory {
  /**
   * datastore providers' package name.
   */
  protected function getPackage(){
    return 'mychaelstyle\\datastore\\providers';
  }
  /**
   * datastore providers dir
   */
  protected function getPath(){
    return dirname(__FILE__).'/providers';
  }
}
