<?php
namespace Skel;

class Post extends Content implements Interfaces\Post {

  protected $author;
  protected $imgPrefix;
  protected $relationships;
  protected $category;
  protected $hasImg;

  protected static $validCategories = array();

  public function __construct(Interfaces\CmsDb $cms, array $sourceData=null) {
    parent::__construct($cms, $sourceData);

    $defaults = array('contentClass' => '\Skel\Post', 'contentType' => 'text/markdown; charset=UTF-8', 'attributes' => array('author' => null, 'imgPrefix' => null, 'category' => null, 'hasImg' => false));

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
      ->setHasImg($data['attributes']['category'])
    ;

    // If we're building from data, consider this a fresh, unchanged object
    if ($sourceData) {
      $this->errors = array();
      $this->changed = array();
    }
  }

  public function createImgPrefix(Interfaces\Post $post) {
    $prefix = $post->getDateCreated()->format('Y-m-');
    $words = explode(' ',$post->getTitle());
    if (count($words) <= 3) return $prefix.$this->createSlug(implode('-', $words));

    $bigWords = array();
    foreach($words as $w) {
      if (strlen($w) > 3 || is_numeric($w)) {
        $bigWords[] = $w;
        if (count($bigWords) == 3) break;
      }
    }

    return $prefix.$this->createSlug(implode('-', $bigWords));
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
  public function getHasImg() { return $this->hasImg; }
  public function getImgPrefix() { return $this->imgPrefix; }
  public function getMainImg() { return $this->mainImg; }
  public function getCategory() { return $this->category; }
  public static function getValidCategories() { return static::$validCategories; }

  public function setAuthor(string $val=null) {
    $this->setAttribute('author', $val);
    $this->author = $val;
    return $this;
  }
  public function setHasImg($val) {
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
    $cats = array();
    foreach ($categories as $k => $v) {
      if (is_numeric($k)) $cats[$v] = $v;
      else $cats[$k] = $v;
    }
    static::$validCategories = $cats;
  }
}


