<?php
namespace Skel;

class Post extends Content  {
  const RENDER_FULL = 1;
  const RENDER_PREVIEW = 2;

  protected static $validTypes = array();
  public static $slugRegex = '/[^A-Za-z0-9+=|}{[\]\'";:\/><.,\\!@#$%^&*()_-]/';


  public function __construct() {
  }

  public static function createFromData($data) {
    if (!is_array($data)) throw new \InvalidArgumentException("`\$data` parameter must be an array");
    $p = new static();
    foreach (static::$data_fields as $d) {
      if (!isset($data[$d])) throw new \InvalidArgumentException("Missing data in Post::fromData(). The passed data array must contain ALL required data fields, as defined by the called class");
      $this->$d = $data[$d];
    }

    return $p;
  }

  public function getAuthor() { return $this->get(static::CONTENT_TABLE_NAME, 'author'); }
  public function getFeatured() { return $this->get(static::CONTENT_TABLE_NAME, 'featured') == 1; }
  public function getImgPrefix() { return $this->get(static::CONTENT_TABLE_NAME, 'img_prefix'); }
  public function getLang() { return new Lang($this->lang); }
  public function getParentId() { return $this->parentId; }
  public function getSlug() { return $this->slug; }
  public function getTags() { return $this->tags; }
  public function getTitle() { return $this-title; }
  public function getType() { return $this->type; }
  public function getValidTypes() { return static::$validTypes; }


  public function render(\Skel\Interfaces\UiManager $uiManager, int $type) {
    $vars = array(
      ''
    );
  }

  public function setAuthor(string $val) { $this->set('author', $val); }
  public function setContent(string $val) { $this->set('content', $val); }
  public function setFeatured(bool $val) { $this->set('featured', $val ? 1 : 0); }
  public function setImgPrefix(string $val) {
    if (!$val) throw new \Skel\DataValidationException("You cannot set your post `img_prefix` to empty");
    $this->set('img_prefix', $val);
  }
  public function setParentId(int $val) { $this->set('parent_id', $val); }

  public static function setValidTypes(array $types) {
    foreach ($types as $k => $v) {
      $types = array();
      if (is_numeric($k)) $types[$v] = $v;
      else $types[$k] = $v;
    }
    static::$validTypes = $types;
  }
}


