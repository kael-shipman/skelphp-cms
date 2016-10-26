<?php
namespace Skel;

class Post extends Content  {

  protected $author;
  protected $imgPrefix;
  protected $relationships;
  protected $category;

  protected static $validCategories = array();

  public function __construct(array $sourceData=null) {
    parent::__construct($sourceData);

    $defaults = array('contentClass' => 'Post', 'contentType' => 'text/markdown; charset=UTF-8', 'author' => null, 'imgPrefix' => null, 'category' => null);

    // If building from data, make sure the source data is complete
    if ($sourceData) {
      $missing = array();
      foreach($defaults as $k => $v) {
        if (!isset($sourceData[$k])) $missing[] = $k;
      }
      if (count($missing) > 0) throw new InvalidDataException("Data passed into the Post constructor must have all of the fields required by a Post. Missing fields: `".implode('`, `', $missing));
      $data = $sourceData;
    } else {
      $data = $defaults;
    }

    // Set fields
    $this
      ->setContentClass($data['contentClass'])
      ->setContentType($data['contentType'])
      ->setAuthor($data['author'])
      ->setImgPrefix($data['imgPrefix'])
      ->setCategory($data['category'])
    ;

    // If we're building from data, consider this a fresh, unchanged object
    if ($sourceData) {
      $this->errors = array();
      $this->changed = array();
    }
  }

  public function getAuthor() { return $this->author; }
  public function getImgPrefix() { return $this->imgPrefix; }
  public function getMainImg() { return $this->mainImg; }
  public function getCategory() { return $this->category; }
  public static function getValidCategories() { return static::$validCategories; }

  public function setAuthor(string $val=null) {
    $this->set('author', $val);
    $this->author = $val;
    return $this;
  }
  public function setImgPrefix(string $val=null) {
    if (!$val) $this->setError('imgPrefix', 'All Posts must have a valid imgPrefix');
    else $this->clearError('imgPrefix');

    $this->set('img_prefix', $val);
    $this->imgPrefix = $val;
    return $this;
  }
  public function setCategory(string $val=null) {
    if (!$val || array_search($val, static::$validCategories) === false) $this->setError('category', 'All Posts must have a category. Currently allowed categories are `'.implode('`, `', static::$validCategories).'`.');
    else $this->clearError('category');

    $this->set('category', $val);
    $this->category = $val;
    return $this;
  }

  public static function setValidCategories(array $categories) {
    $cats = array();
    foreach ($categories as $k => $v) {
      if (is_numeric($k)) $cats[$v] = $v;
      else $cats[$k] = $v;
    }
    static::$validCategories = $cats;
  }
}


