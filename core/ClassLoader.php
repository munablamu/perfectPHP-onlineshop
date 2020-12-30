<?php

/**
 * Class to manage autoloading of class files.
 *
 * @var array $dirs
 */
class ClassLoader
{
  protected $dirs;

  /**
   * Register the autoloader class.
   *
   * @return void
   */
  public function register()
  {
    spl_autoload_register([$this, 'loadClass']);
  }

  /**
   * Register the directory where the autoloader class files exist.
   *
   * @param  string $dir
   * @return void
   */
  public function registerDir($dir)
  {
    $this->dirs[] = $dir;
  }

  /**
   * Automatically called by PHP at autoloading.
   *
   * @param  string $class
   * @return null
   */
  public function loadClass($class)
  {
    foreach ($this->dirs as $dir) {
      $file = $dir . '/' . $class . '.php';
      if ( is_readable($file) ) {
        require $file;

        return;
      }
    }
  }
}
