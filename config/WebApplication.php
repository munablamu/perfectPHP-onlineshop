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
    if ( $this->debug ) {
      $this->db_manager->connect('master',
                                 [ 'driver'   => 'mysql',
                                   'dbname'   => 'train_db',
                                   'host'     => 'shop_mysql_1',
                                   'port'     => '3306',
                                   'user'     => 'root',
                                   'password' => 'root-password', ]);
    } else {
      $url = parse_url(getenv('DATABASE_URL'));

      $this->db_manager->connect('master',
                                 [ 'driver'   => 'pgsql',
                                   'dbname'   => substr($url['path'], 1),
                                   'host'     => $url['host'],
                                   'port'     => $url['port'],
                                   'user'     => $url['user'],
                                   'password' => $url['pass'], ]);
    }
  }
}
