<?php

/**
 * Class to access a database. Create a child class of this class for each table.
 *
 * @var PDO $pdo
 */
abstract class DbRepository
{
  protected $pdo;

  /**
   * Constructor
   *
   * @param PDO $pdo
   */
  public function __construct($pdo)
  {
    $this->setConnection($pdo);
  }

  /**
   * Validate the registration process.
   *
   * @param  array $inputs
   * @param  array &$errors
   * @return void
   */
  abstract public function validateRegister($inputs, &$errors);

  /**
   * Connection instance setter
   *
   * @param  PDO  $pdo
   * @return void
   */
  public function setConnection($pdo)
  {
    $this->pdo = $pdo;
  }

  /**
   * Execute the prepared statement and get the result instance.
   * [NOTICE] TODO: 連想配列ではなくて、クラスにすべき
   * $params[array] should be a multidimensional associative array below.
   * array(array('id' => ':name', 'value' => @name, 'type' => 'string' ), ...)
   *
   * @param  string       $sql
   * @param  array        $params = array()
   * @return PDOStatement
   */
  public function execute($sql, $params = array())
  {
    try {
      if ( $stmt = $this->pdo->prepare($sql) ) {
        // Bind variables to the prepared statement as parameters
        if ( !empty($params) ) foreach ( $params as $param ) {
          if ( get_class($param) === 'DbField' ) {
            $stmt->bindValue($param->getKey(), $param->getValue(), $param->getType());
          } else {
            throw new InvalidArgumentException('Use DbField Class. ' . $param);
          }
        }
        unset($param);

        $stmt->execute();
      }
    } catch ( PDOException $e ) {
      die("ERROR: Could not prepare/execute query: $sql. " . $e->getMessage());
    } catch ( InvalidArgumentException $e ) {
      die("ERROR: " . $e->getMessage());
    }

    return $stmt;
  }

  /**
   * Execute a SELECT statement and get only one row of results.
   * [NOTICE] TODO: 連想配列ではなくて、クラスにすべき
   * $params[array] should be a multidimensional associative array below.
   * array(array('id' => ':name', 'value' => @name, 'type' => 'string' ), ...)
   *
   * @param  string $sql
   * @param  array  $params = array()
   * @return array
   */
  public function fetch($sql, $params = array())
  {
    return $this->execute($sql, $params)->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Execute a SELECT statement and get all row of results.
   * [NOTICE] TODO: 連想配列ではなくて、クラスにすべき
   * $params[array] should be a multidimensional associative array below.
   * array(array('id' => ':name', 'value' => @name, 'type' => 'string' ), ...)
   *
   * @param  string $sql
   * @param  array  $params = array()
   * @return array
   */
  public function fetchAll($sql, $params = array())
  {
    return $this->execute($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
  }
}
