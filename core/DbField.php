<?php

class DbField
{
  protected $key;
  protected $value;
  protected $type;

  /**
   * __construct
   *
   * @param  string $key
   * @param  mixed  $value
   * @param  string $type = 'string'
   * @throws InvalidArgumentException
   */
  public function __construct($key, $value, $type='string')
  {
    $this->key = $key;
    $this->setValueAndType($value, $type);
  }

  /**
   * Set $value and $type.
   *
   * @param  mixed  $value
   * @param  string $type
   * @throws InvalidArgumentException
   */
  protected function setValueAndType($value, $type) {
    switch ($type) {
      case 'string':
        $this->value = strval($value);
        $this->type  = PDO::PARAM_STR;
        break;
      case 'int':
        $this->value = intval($value);
        $this->type  = PDO::PARAM_INT;
        break;
      case 'bool':
        // PDO::PARAM_BOOL should not be used
        $this->value = boolval($value);
        $this->type  = PDO::PARAM_INT;
        break;
      case 'date':
        $this->value = $value->format('Y-m-d');
        $this->type  = PDO::PARAM_STR;
        break;
      case 'datetime':
        $this->value = $value->format('Y-m-d H:i:s');
        $this->type  = PDO::PARAM_STR;
        break;
      case 'null':
        $this->value = null;
        $this->type  = PDO::PARAM_NULL;
        break;
      default:
        throw new InvalidArgumentException('Type is invalid: ' . $type);
        break;
    }
  }

  /**
   * get $key
   *
   * @return string
   */
  public function getKey()
  {
    return $this->key;
  }

  /**
   * get $value
   *
   * @return mixed
   */
  public function getValue()
  {
    return $this->value;
  }

  /**
   * get $type
   *
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
}
