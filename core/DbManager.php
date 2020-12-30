<?php
/**
 * Class to manage connections to databases.
 *
 * @var array $connections               [$pdo, ...]
 * @var array $repository_connection_map [$repository_name => $pdo_name, ...]
 * @var array $repositories              [$repository, ...]
 */
class DbManager
{
  protected $connections               = array();
  protected $repository_connection_map = array();
  protected $repositories              = array();

  /**
   * Connect to the database.
   *
   * @param  string $name
   * @param  array  $params
   * @param  array  $attributes = array()
   * @return void
   */
  public function connect($name, $params, $attributes=array())
  {
    // Set the initial value of $params
    $params = array_merge([
      'driver'   => null,
      'dbname'   => '',
      'host'     => '',
      'port'     => '',
      'user'     => '',
      'password' => '',
      'options'  => array(),
    ], $params);

    $params['dsn'] = $params['driver'];
    if ( $params['driver'] === 'mysql' || $params['driver'] == 'pgsql' ) {
      $this->setMysqlDsn($params);
    } elseif ( $params['driver'] === 'sqlite' ) {
      $this->setSqliteDsn($params);
    } else {
      die('ERROR: Your database driver is invalid. ' . $params['driver']);
    }

    try {
      $pdo = new PDO($params['dsn'], $params['user'], $params['password'], $params['options']);

      $defautls = [
        // Raise an exception when an error occurs inside PDO
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // Return the result set as an associative array subscripted by column name
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Prohibit compound statement execution in MYSQL
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
        // Use static placeholders
        PDO::ATTR_EMULATE_PREPARES => false
      ];

      $attributes = array_merge($defautls, $attributes);

      foreach ( $attributes as $attribute => $value ) {
        $pdo->setAttribute($attribute, $value);
      }

      $this->connections[$name] = $pdo;
    } catch ( PDOException $e ) {
      die('ERROR: Could not connect. ' . $e->getMessage());
    }
  }

  /**
   * [Destructive] Set dsn for mysel
   *
   * @param  array &$params
   * @return void
   */
  protected function setMysqlDsn(&$params) {
    if ( empty($params['dbname']) ) {
      $params['dsn'] = $params['driver'] . ':host=' . $params['host'];
    } else {
      $params['dsn'] = $params['driver']
                     . ':dbname=' . $params['dbname']
                     . ';host=' . $params['host'];
    }

    if ( !empty($params['port']) ) {
      $params['dsn'] .= ';port=' . $params['port'];
    }
  }

  /**
   * [Destructive] Set dsn for SQLite
   *
   * @param array &$params
   * @return void
   */
  protected function setSqliteDsn(&$params) {
    if ( empty($params['dbname']) ) {
      $params['dsn'] = $params['driver'];
    } else {
      $params['dsn'] = $params['driver'] . ':' . $params['dbname'];
    }
  }

  /**
   * Get connected PDO instance.
   *
   * @param  string $name
   * @return PDO
   */
  public function getConnection($name = null)
  {
    if ( is_null($name) ) {
      return current($this->connections);
    }

    return $this->connections[$name];
  }

  /**
   * Map between a DbRepository and a table connection
   *
   * @param  string $repository_name
   * @param  string $name
   * @retuen void
   */
  public function setRepositoryConnectionMap($repository_name, $name)
  {
    $this->repository_connection_map[$repository_name] = $name;
  }

  /**
   * Get connected PDO instance associated with the repository name.
   *
   * @param  string repository_name
   * @return PDO
   */
  public function getConnectionForRepository($repository_name)
  {
    if ( isset($this->repository_connection_map[$repository_name]) ) {
      $name = $this->repository_connection_map[$repository_name];
      $pdo = $this->getConnection($name);
    } else {
      $pdo = $this->getConnection();
    }

    return $pdo;
  }

  /**
   * Get DbRepository instance.
   *
   * @param  string       $repository_name
   * @return DbRepository
   */
  protected function get($repository_name)
  {
    if ( !isset($this->repositories[$repository_name]) ) {
      $repository_class = $repository_name . 'Repository';
      $pdo = $this->getConnectionForRepository($repository_name);

      $repository = new $repository_class($pdo);

      $this->repositories[$repository_name] = $repository;
    }

    return $this->repositories[$repository_name];
  }


  /**
   * Destructor
   */
  public function __destruct()
  {
    // It is not possible to unset $pdo without deleting $repository first, because $pdo are
    // referenced in the $repository.
    foreach ( $this->repositories as $repository ) {
      unset($repository);
    }

    foreach ($this->connections as $pdo ) {
      unset($pdo);
    }
  }

  /**
   * __get
   *
   * @return mixed
   */
  public function __get($name)
  {
    return $this->get($name);
  }

  /**
   * __isset
   *
   * @return bool
   */
  public function __isset($name)
  {
    return isset($this->repositories[$name]);
  }

  /**
   * __unset
   *
   * @return void
   */
  public function __unset($name)
  {
    unset($this->repositories[$name]);
  }
}
