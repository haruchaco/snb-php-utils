<?php
/**
 * mychaelstyle\db\Provider.php interface file.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the Apache2 License. For more information please see
 * <http://github.com/mychaelstyle>
 *
 * @package mychaelstyle
 * @subpackage db
 * @auther Masanori Nakashima
 */

namespace mychaelstyle\db;
require_once dirname(dirname(__FILE__)).'/Provider.php';

/**
 * dbプロバイダーインターフェース
 * <p>
 * dbプロバイダのインターフェースクラス。
 * すべてのdbプロバイダクラスは本インターフェースを実装する。
 * </p>
 * @author    Masanori Nakashima <>
 * @version   $Id$
 * @package mychaelstyle
 * @subpackage db
 * @auther Masanori Nakashima
 */
interface Provider extends \mychaelstyle\Provider {
  public function getConnection($sql=null);
}
