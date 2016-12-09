<?php
namespace Skel;

class ContentTag extends DataClass {
  const TABLE_NAME = "tags";

  public function __construct(array $e=array(), Interfaces\Template $t=null) {
    parent::__construct($e, $t);
    $this->addDefinedFields(array("tag"));
  }



  protected function typecheckAndConvertInput(string $field, $val) {
    if ($val === null || $val instanceof DataCollection) return $val;

    if ($field == 'tag') {
      if (!is_string($val)) throw new \InvalidArgumentException("Field `$field` must be a string.");
      return $val;
    }
    return parent::typecheckAndConvertInput($field, $val);
  }

  protected function validateField(string $field) {
    $val = $this->get($field);
    $required = array(
      'tag' => "You must specify a tag name",
    );

    if (array_key_exists($field, $required) && ($val === null || $val === '')) $this->setError($field, $required[$field], 'required');
    else $this->clearError($field, 'required');
  }
}


?>

