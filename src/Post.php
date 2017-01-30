<?php
namespace Skel;

class Post extends Page implements Interfaces\Post {
  public function __construct(array $elements=array(), Interfaces\Template $t=null) {
    $this->addDefinedFields(array('author'));
    parent::__construct($elements, $t);
  }





  public function getAuthor() { return $this['author']; }






  protected function typecheckAndConvertInput(string $field, $val) {
    if ($val === null) return $val;

    if ($field == 'author') {
      if (!is_string($val)) throw new \InvalidArgumentException("Field `$field` must be a string!");
      return $val;
    }
    return parent::typecheckAndConvertInput($field, $val);
  }
}


