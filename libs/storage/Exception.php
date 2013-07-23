<?php
/**
 * snb\storage\Exception.php class file.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the Apache2 License. For more information please see
 * <http://github.com/haruchaco>
 *
 * @author  Masanori Nakashima <>
 * @version $Id$
 * @package snb
 * @subpackage storage
 */

namespace snb\storage;

/**
 * Original exception class for snb\storage.
 *
 * snb\storageパッケージ用の例外クラス。
 * 
 * @author  Masanori Nakashima <>
 * @version $Id$
 * @package snb
 * @subpackage storage
 */
class Exception extends \Exception {
  /**
   * Error code : unkown error
   */
  const ERROR_UNKOWN = 0;
  /**
   * Error code: file io
   */
  const ERROR_IO = 10;
  /**
   * error code: provider connection.
   */
  const ERROR_PROVIDER_CONNECTION = 20;
  /**
   * related exceptions
   */
  public $related_exceptions = array();
  /**
   * Constructor
   */
  public function __construct($message=null,$code=0,$previous=null,$relatedExceptions=array()){
    parent::__construct($message,$code,$previous);
    $this->related_exceptions = $relatedExceptions;
  }
}
