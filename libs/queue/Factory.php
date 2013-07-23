<?php
/**
 * snb\queue\Factory
 * @package snb
 * @subpackage queue
 * @auther Masanori Nakashima
 */
namespace snb\queue;
require_once dirname(dirname(__FILE__)).'/ProviderFactory.php';
/**
 * snb\queue\Factory
 * @package snb
 * @subpackage queue
 * @auther Masanori Nakashima
 */
class Factory extends \snb\ProviderFactory {
  /**
   * queue providers' package name.
   */
  protected function getPackage(){
    return 'snb\\queue\\providers';
  }
  /**
   * queue providers dir
   */
  protected function getPath(){
    return dirname(__FILE__).'/providers';
  }
}
