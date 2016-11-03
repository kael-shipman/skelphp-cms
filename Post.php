<?php
namespace Skel;

class Post extends Content implements Interfaces\Post {

  protected $imgPrefix;
  protected $category;
  protected $hasImg;

  protected static $validCategories = array();
  protected static $__validCategories = array();

  public function __construct(array $data=array()) {
    parent::__construct($data);

    $this
      ->setContentType($data['contentType'] ?: 'text/markdown; charset=UTF-8')
      ->setImgPrefix($data['imgPrefix'])
      ->setCategory($data['category'])
      ->setHasImg((bool) $data['hasImg'])
    ;

    // If we're building from data, consider this a fresh, unchanged object
    if (count($data) > 0) $this->changed = array();
  }

  public function createImgPrefix() {
    $prefix = $this->getDateCreated()->format('Y-m-');
    $words = explode(' ',$this->getTitle());
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
  public function getCategory() { return $this->category; }
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
  public function getImgPrefix() { return $this->imgPrefix; }
  public function getMainImgPath() { return $this->hasImg ? '/assets/imgs/'.$this->category.'/'.$this->imgPrefix.'.jpg' : null; }
  public static function getValidCategories() {
    if (static::$__validCategories) return static::$__validCategories;

    $cats = static::$validCategories;
    $parent = static::class;
    while ($parent = get_parent_class($parent)) $cats = array_merge($cats, $parent::$validCategories ?: array());
    static::$__validCategories = $cats;

    return static::$__validCategories;
  }
  public function hasImg() { return $this->hasImg; }




  public function setAuthor(string $val=null) {
    $this->setAttribute('author', $val);
    $this->author = $val;
    return $this;
  }
  public function setHasImg(bool $val) {
    if (!is_bool($val) && !is_numeric($val)) $this->setError('hasImg', 'The HasImg flag must evaluate to true or false.');
    else $this->clearError('hasImg');

    $this->setAttribute('hasImg', (int) $val);
    $this->hasImg = (bool)$val;
    return $this;
  }
  public function setImgPrefix(string $val=null) {
    if (!$val) $this->setError('imgPrefix', 'All Posts must have a valid imgPrefix.');
    else $this->clearError('imgPrefix');

    $this->setAttribute('imgPrefix', $val);
    $this->imgPrefix = $val;
    return $this;
  }
  public function setCategory(string $val=null) {
    if (!$val || array_search($val, static::$validCategories) === false) $this->setError('category', 'All Posts must have a category. Currently allowed categories are `'.implode('`, `', static::$validCategories).'`.');
    else $this->clearError('category');

    $this->setAttribute('category', $val);
    $this->category = $val;
    return $this;
  }

  public static function setValidCategories(array $categories) {
    static::$validCategories = $categories;
  }



  protected function validate(string $field) {
    $val = $this->$field;
    $required = array(
      'imgPrefix' => 'All posts must have an imgPrefix, even if they don\'t have any images',
      'category' => 'All posts must be assigned to a valid category. Otherwise they won\'t show up anywhere!';
    );

    if (array_key_exists($field, $required) && !$val) $error = $required[$field];

    if ($field == 'hasImg') {
      if (!is_bool($val)) $error = 'hasImg must be true or false. It can\'t be null.';
    } elseif ($field == 'category') {
      if (array_search($val, static::getValidCategories()) === false) $error = $required[$field];
    }

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


