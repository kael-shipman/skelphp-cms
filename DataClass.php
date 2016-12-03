<?php
namespace Skel;

abstract class DataClass extends ValidatedComponent implements Interfaces\DataClass, Interfaces\ErrorHandler {
  use ErrorHandlerTrait;

  protected $changes = array();
  protected $setBySystem = array();





  // Constructors
  public function __construct(array $elements=array(), Interfaces\Template $t=null) {
    parent::__construct($elements, $t);
    $this->addValidFields(array('id'));
    $this->set('id', null, true);
  }

  public static function createFromUserInput(array $data) {
    $o = new static();
    foreach($o->getValidFields() as $field) $o->set($field, $o->convertDataToField($field, $data[$field]), false);
    return $o;
  }

  public static function restoreFromData(array $data) {
    $setBySystem = json_decode($data['setBySystem']);
    unset($data['setBySystem']);

    $o = static::createFromUserInput($data);

    $o->setBySystem = $setBySystem;
    $o->changes = array();
    return $o;
  }







  // Data Handling

  public function get($field) {
    return $this->convertDataToField($field, $this->elements[$field]);
  }

  public function getRaw($field) {
    return $this->elements[$field];
  }

  public function set(string $field, $val, bool $setBySystem=false) {
    if ($val instanceof DataCollection) {
      $this->elements[$field] = $val;
      return $this;
    }

    $val = $this->typecheckAndConvertInput($field, $val);
    $prevVal = $this[$field];

    if ($val != $prevVal || $prevVal === null) {
      if (!array_key_exists($field, $this->changes)) $this->changes[$field] = array();
      $this->changes[$field][] = $prevVal;
    }

    $this->elements[$field] = $val;
    $this->setBySystem[$field] = $setBySystem;
    $this->validateField($field);

    if ($val != $prevVal) $this->notifyListeners('Change', array('field' => $field, 'prevVal' => $prevVal, 'newVal' => $val));

    return $this;
  }







  // Utility


  
  // Public

  public function fieldSetBySystem(string $field) { return (bool)$this->setBySystem[$field]; }
  public function getChanges() { return $this->changes; }
  public function fieldHasChanged(string $field) { return array_key_exists($field, $this->changes); }
  public function getFieldsSetBySystem() {
    $fields = array();
    foreach($this->setBySystem as $field => $set) {
      if ($set) $fields[] = $field;
    }
    return $fields;
  }




  // Internal

  protected function convertDataToField(string $field, $dataVal) {
    return $dataVal;
  }

  public static function getNormalizedClassName() {
    $str = explode('\\', static::class);
    $str = array_pop($str);
    $str = preg_replace(array('/([A-Z])/', '/_-/'), array('-\1','_'), $str);
    return trim(strtolower($str), '-');
  }

  protected function typecheckAndConvertInput(string $field, $val) {
    if ($val === null) return $val;

    if ($field == 'id') {
      if ($this->get($field) !== null) throw new InvalidDataException("You can't change an id that's already been set!");
      return $val;
    }
    throw new UnknownFieldException("`$field` is not a known field for this object! All known fields must be type-checked and converted on input using the `typecheckAndConvertInput` function.");
  }




  // Overrides

  public function offsetGet($key) {
    parent::offsetGet($key);
    return $this->get($key);
  }
  public function offsetSet($key, $val) {
    $oldVal = $this->elements[$key];
    parent::offsetSet($key, $val);
    $this->elements[$key] = $oldVal;
    $this->set($key, $val);
  }
  public function offsetUnset($key) {
    $this->set($key, null);
  }




  // Abstract

  abstract protected function validateField(string $field);
}


