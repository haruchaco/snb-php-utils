<?php
/**
 * mychaelstyle\storage\Provider.php interface file.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the Apache2 License. For more information please see
 * <http://github.com/mychaelstyle>
 *
 * @package mychaelstyle
 * @subpackage storage
 * @auther Masanori Nakashima
 */

namespace mychaelstyle\storage;
require_once dirname(dirname(__FILE__)).'/Provider.php';

/**
 * ストレージプロバイダーインターフェース
 * <p>
 * ストレージプロバイダのインターフェースクラス。
 * すべてのストレージプロバイダクラスは本インターフェースを実装する。
 * </p>
 * @author    Masanori Nakashima <>
 * @version   $Id$
 * @package mychaelstyle
 * @subpackage storage
 * @auther Masanori Nakashima
 */
interface Provider extends \mychaelstyle\Provider {
  /**
   * get contents from uri
   */
  public function get($uri,$path=null);
	/**
	 * put file
	 * @param string $srcPath
	 * @param string $dstUri
   * @param array $options
	 */
	public function put($srcPath,$dstUri,$options=array());
	/**
	 * remove file or folder
	 * @param string $dstUri
	 * @param boolean $recursive
	 */
	public function remove($dstUri,$recursive=false);

}
