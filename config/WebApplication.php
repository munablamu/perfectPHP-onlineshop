<?php

class WebApplication extends Application
{
  protected $login_action = [ 'controller' => '',
                              'action'     => '', ];

  /**
   * Return root directory.
   *
   * @return void
   */
  public function getRootDir()
  {
    return dirname(__FILE__) . '/..';
  }

  /**
   * Return routing defined array.
   *
   * @return array
   */
  public function registerRoutes()
  {
    return [];
  }

  /**
   * Configre
   *
   * @return void
   */
  protected function configure()
  {
    $this->db_manager->connect('master',
                               [ 'driver'   => 'mysql',
                                 'dbname'   => 'train_db',
                                 'host'     => 'shop_mysql_1',
                                 'port'     => '3306',
                                 'user'     => 'root',
                                 'password' => 'root-password', ]);
  }
}
