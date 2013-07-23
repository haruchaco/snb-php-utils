<?php
/**
 * snb\ProviderFactory
 * @package snb
 * @auther Masanori Nakashima
 */
namespace snb;
require_once dirname(__FILE__).'/Provider.php';
require_once dirname(__FILE__).'/Exception.php';
/**
 * Service provider factory abstract class
 * @package snb
 * @auther Masanori Nakashima
 */
abstract class ProviderFactory {
  /**
   * @var string name
   */
  protected $name;
  /**
   * @var string $uri
   */
  protected $uri;
  /**
   * get providers' package name.
   * @return string package name;
   */
  abstract protected function getPackage();
  /**
   * get provider files dir path
   * @return string dir path
   */
  abstract protected function getPath();
  /**
   * get instance
   * @param string $dsn
   * @param array $options
   */
  public function getProvider($dsn,$options=array()){
    $object = $this->getClass($dsn,$this->getPackage(),$this->getPath());
    $object->connect($this->uri,$options);
    return $object;
  }
 /**
   * get provider class name
   * @param string $dsn
   * @return string driver name
   */
  protected function getClass($dsn,$package,$path){
    if(is_null($this->name)){
      $this->perse($dsn);
    }
    $baseName = $this->name;
    $baseName = str_replace('_','',$baseName);
    $filePath = $path.'/'.$baseName.'.php';
    $className = $package.'\\'.$baseName;
    if(file_exists($filePath)){
      require_once $filePath;
    }
    if(class_exists($className)){
      return new $className;
    }
    throw new \snb\Exception('Provider class '.$className.' is not found.',\snb\Exception::ERROR_PROVIDER_CONNECTION);
  }
  /**
   * set DSN
   * @param string $dsn
   */
  protected function perse($dsn){
    if(false===strpos($dsn,'://')){
      throw new \snb\Exception('The dsn provider name is null!',
        \snb\Exception::ERROR_PROVIDER_CONNECTION);
    }
    list($this->name, $this->uri) = explode('://',$dsn);
    if(strlen(trim($this->name))==0){
      throw new \snb\Exception('The dsn provider name is null!',
        \snb\Exception::ERROR_PROVIDER_CONNECTION);
    } else if(strlen(trim($this->uri))==0){
      throw new \snb\Exception('The provider root is null!',
        \snb\Exception::ERROR_PROVIDER_CONNECTION);
    }
  }
}
