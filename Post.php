<?php
namespace Skel;

class Post extends Page implements Interfaces\Post {
  public function __construct(array $elements=array(), Interfaces\Template $t=null) {
    parent::__construct($elements, $t);
    $this->addFields(array('author','hasImg','imgPrefix'));
    $this
      ->set('author', null, true)
      ->set('hasImg', null, true)
      ->set('imgPrefix', null, true)
    ;
  }







  public static function createImgPrefix(\DateTime $dateCreated, string $title) {
    $prefix = $dateCreated->format('Y-m-');
    $words = explode(' ',$title);
    if (count($words) <= 3) return $prefix.static::createSlug(implode('-', $words));

    $bigWords = array();
    foreach($words as $w) {
      if (strlen($w) > 3 || is_numeric($w)) {
        $bigWords[] = $w;
        if (count($bigWords) == 3) break;
      }
    }

    return $prefix.static::createSlug(implode('-', $bigWords));
  }

  public function getAncestors() {
    $path = explode('/', trim($this->getParentAddress(), '/'));
    $section = array_shift($path);
    $a = array();
    $ancestors = array();
    foreach($path as $p) {
      $a[] = $p;
      $ancestors[] = '/'.implode('/',$a);
    }
    return $ancestors;
  }

  public function hasImg() { return $this->get('hasImg'); }








  protected function convertDataToField(string $field, $dataVal) {
    if ($field == 'hasImg') return (bool)$dataVal;
    return parent::convertDataToField($field, $dataVal);
  }

  protected function onFieldChange(Interfaces\DataClass $dataclass, string $field, $oldVal, $newVal) {
    if ($field == 'dateCreated' || $field == 'title') {
      if ($this->fieldSetBySystem('imgPrefix') && ($title = $this->get('title')) && ($date = $this->get('dateCreated'))) $this->set('imgPrefix', static::createImgPrefix($date, $title), true);
    }
    return parent::onFieldChange($dataclass,$field,$oldVal,$newVal);
  }

  protected function typecheckAndConvertInput(string $field, $val) {
    if ($val === null) return $val;

    if ($field == 'hasImg') {
      if (!is_bool($val)) throw new \InvalidArgumentException("Field `$field` must be a boolean value!");
      return (int)$val;
    }
    if ($field == 'author' || $field == 'imgPrefix') {
      if (!is_string($val)) throw new \InvalidArgumentException("Field `$field` must be a string!");
      return $val;
    }
    return parent::typecheckAndConvertInput($field, $val);
  }

  protected function validateField(string $field) {
    $val = $this->$field;
    $required = array(
      'hasImg' => 'All posts must have the `hasImg` flag set to true or false',
      'imgPrefix' => 'All posts must have an imgPrefix, even if they don\'t have any images',
    );

    if (array_key_exists($field, $required) && !$val) $this->setError($field, $required[$field], 'required');
    else $this->clearError($field, 'required');

    if ($field != 'hasImg' && $field != 'imgPrefix') parent::validateField($field);
  }
}


