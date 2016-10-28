<?php
namespace Skel;

class Post extends Content implements Interfaces\Post {

  protected $author;
  protected $imgPrefix;
  protected $relationships;
  protected $category;

  protected static $validCategories = array();

  public function __construct(Interfaces\CmsDb $cms, array $sourceData=null) {
    parent::__construct($cms, $sourceData);

    $defaults = array('contentClass' => '\Skel\Post', 'contentType' => 'text/markdown; charset=UTF-8', 'attributes' => array('author' => null, 'imgPrefix' => null, 'category' => null));

    // If building from data, make sure the source data is complete
    if ($sourceData) {
      $missing = array();
      foreach($defaults as $k => $v) {
        if (!array_key_exists($k, $sourceData)) $missing[] = $k;
      }
      if (count($missing) > 0) throw new InvalidDataException("Data passed into the Post constructor must have all of the fields required by a Post. Missing fields: `".implode('`, `', $missing).'`.');
      $data = $sourceData;
    } else {
      $data = $defaults;
    }

    // Set fields
    $this
      ->setContentClass($data['contentClass'])
      ->setContentType($data['contentType'])
      ->setAuthor($data['attributes']['author'])
      ->setImgPrefix($data['attributes']['imgPrefix'])
      ->setCategory($data['attributes']['category'])
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
    $this->setAttribute('author', $val);
    $this->author = $val;
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
    $cats = array();
    foreach ($categories as $k => $v) {
      if (is_numeric($k)) $cats[$v] = $v;
      else $cats[$k] = $v;
    }
    static::$validCategories = $cats;
  }
}


