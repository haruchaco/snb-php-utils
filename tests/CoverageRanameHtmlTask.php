<?php
/**
 * Phing Task rename the html file create by PHPUnit.
 * This file created by Masanori Nakashima.
 * 
 */
require_once 'phing/Task.php';
require_once 'phing/system/io/PhingFile.php';
require_once 'phing/system/io/Writer.php';
require_once 'phing/system/util/Properties.php';
require_once 'phing/tasks/ext/phpunit/PHPUnitUtil.php';
require_once 'phing/tasks/ext/coverage/CoverageReportTransformer.php';

/**
 * Transforms information in a code coverage database to XML
 *
 * @author Masanori Nakashima <haruchaco@gmail.com>
 * @version $Id: 564bbde3ec5084ed2db570958548af2b9d1c1127 $
 * @package phing.tasks.ext.coverage
 * @since 2.1.0
 */
class CoverageRemaneHtmlTask extends Task
{
  public main(){

  }
}
