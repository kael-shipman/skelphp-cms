<?php
namespace Skel;

class Post extends Content implements Interfaces\Post {

  protected $author;
  protected $hasImg;
  protected $imgPrefix;

  protected function loadFromData(array $data) {
    parent::loadFromData($data);

    $fields = array('author', 'hasImg', 'imgPrefix');
    foreach ($fields as $field) {
      if (!array_key_exists($field, $data)) throw new InvalidDataException("Required field `$field` isn't set in the data to load into Post.");

      if ($field == 'hasImg') $val = (bool) $data[$field];
      else $val = $data[$field];

      $this->$field = $val;
    }
  }

  protected function loadDefaults() {
    parent::loadDefaults();
    $this->setAuthor()->setHasImg()->setImgPrefix();
    $this->setBySystem = array_merge($this->setBySystem, array('author' => true, 'hasImg' => true, 'imgPrefix' => true));
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




  public function getAuthor() {
    if (!$this->author) $author = 'Anonymous';
    else $author = $this->author;
    return $author;
  }
  public function getContentExcerpt(int $words=40) {
    $content = $this->getContent();
    $nonwords = array(9 => true, 10 => true, 13 => true, 32 => true);
    $n = 1;
    $wordcount = 0;
    $prevChar = $content[0];
    while($wordcount < $words && $n < strlen($content)) {
      $char = ord($content[$n++]);
      if (isset($nonwords[$char])) {
        if (isset($nonwords[$prevChar])) continue;
        $wordcount++;
      }
      $prevChar = $char;
    }
    return substr($content, 0, $n);
  }
  public function hasImg() { return $this->hasImg; }
  public function getImgPrefix() { return $this->imgPrefix; }
  public function getMainImgPath() {
    if (!$this->parentAddress || !$this->imgPrefix || !$this->hasImg) return null;
    return '/assets/imgs'.$this->parentAddress.'/'.$this->imgPrefix.'.jpg';
  }



  public function setAuthor(string $newVal=null, bool $setBySystem=false) {
    $this->setData('author', $newVal, $setBySystem);
    $this->validate('author');
    return $this;
  }
  public function setDateCreated(\DateTime $newVal=null, bool $setBySystem=false) {
    parent::setDateCreated($newVal, $setBySystem);
    if ($this->fieldSetBySystem('imgPrefix') && $this->title && $this->dateCreated) $this->setImgPrefix(static::createImgPrefix($this->dateCreated, $this->title), true);
    return $this;
  }
  public function setHasImg(bool $val=false, bool $setBySystem=false) {
    $this->setData('hasImg', $val, $setBySystem);
    $this->validate('hasImg');
    return $this;
  }
  public function setImgPrefix(string $newVal=null, $setBySystem=false) {
    $this->setData('imgPrefix', $newVal, $setBySystem);
    $this->validate('imgPrefix');
    return $this;
  }
  public function setTitle(string $newVal=null, $setBySystem=false) {
    parent::setTitle($newVal);
    if ($this->fieldSetBySystem('imgPrefix') && $this->title && $this->dateCreated) $this->setImgPrefix(static::createImgPrefix($this->dateCreated, $this->title), true);
    return $this;
  }



  protected function validate(string $field) {
    $val = $this->$field;
    $required = array(
      'hasImg' => 'All posts must have the `hasImg` flag set',
      'imgPrefix' => 'All posts must have an imgPrefix, even if they don\'t have any images',
    );

    if (array_key_exists($field, $required) && $val === null) $error = $required[$field];

    if ($error) {
      $this->setError($field, $error);
      return false;
    } else {
      if (!parent::validate($field)) return false;

      $this->clearError($field);
      return true;
    }
  }
}


