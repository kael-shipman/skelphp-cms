<?php
namespace Skel;

/**
 * Basic content class for Skel framework
 *
 * This class is sufficient for the most basic text-based content. It will generally
 * be extended.
 *
 * @known_direct_subclasses Page
 * */
class Content {
  protected $active;
  protected $addresses = array();
  protected $attributes = array();
  protected $canonicalAddr;
  protected $contentClass;
  protected $contentType;
  protected $contentUri;
  protected $dateCreated;
  protected $dateExpired;
  protected $dateUpdated;
  protected $id;
  protected $lang;
  protected $tags = array();
  protected $title;

  protected $db;
  protected $rawData;
  protected $changes = array();

  public function __construct() {
    // Define defaults for new content
    $this
      ->setActive(true)
      ->setContentClass('content')
      ->setContentType('text/plain; charset=UTF-8')
      ->setDateCreated(new \DateTime())
      ->setDateUpdated($this->dateCreated)
      ->setLang('en');
  }






  /**
   * Restores a Content object from serialized data in db
   *
   * @return Content
   */
  public static function createFromData($data) {
    $content = new Content();
    $content->id = $data['id'];
    $content->active = (bool) $data['active'];
    $content->addresses = $data['addresses'];
    $content->attributes = $data['attributes'];
    $content->canonicalAddr = $data['canonicalAddr'];
    $content->content = $data['content'];
    $content->contentClass = $data['contentClass'];
    $content->contentType = $data['contentType'];
    $content->contentUri = new Uri($data['contentUri']);
    $content->dateCreated = \DateTime::createFromFormat(\DateTime::ISO8601, $data['dateCreated']);
    $content->dateExpired = \DateTime::createFromFormat(\DateTime::ISO8601, $data['dateExpired']);
    $content->dateUpdated = \DateTime::createFromFormat(\DateTime::ISO8601, $data['dateUpdated']);
    $content->lang = $data['lang'];
    $content->tags = $data['tags'];
    $content->title = $data['title'];

    $content->rawData = $data;
    return $content;
  }

  public function save() {
    if (!$this->db) throw new \RuntimeException("You must set a datasource using `setDatasource` before attempting to persist changes");

    if (count($this->changes) === 0) return true;

    $data = array();
    foreach($this->changes as $field => $prevVal) $data[$field] = $this->rawData[$field];
    $id = $this->db->saveContentData($this->id, $data);
    if (!$this->id) $this->id = $id;
  }

  public function setDatasource(Interfaces\Db $db) {
    $this->db = $db;
  }









  /***************************
   * Setters
   * ************************/

  protected function set($field, $val) {
    if (!isset($this->changes[$field])) $this->changes[$field] = array();
    $this->changes[$field][] = $this->rawData[$field];
    $this->rawData[$field] = $val;
  }

  public function setActive(bool $newVal) {
    $this->set('active', (int) $newVal);
    $this->active = $newVal;
    return $this;
  }

  public function setAttribute(string $key, $newVal) {
    if (isset($this->attributes[$key]) && $this->attributes[$key] == $newVal) return $this;

    $this->attributes[$key] = $newVal;
    $this->set('attributes', $this->attributes);
    return $this;
  }

  public function removeAttribute(string $key) {
    if (!isset($this->attributes[$key])) return $this;
    unset($this->attributes[$key]);
    $this->set('attributes', $this->attributes);
    return $this;
  }

  public function setCanonicalAddr(string $newVal) {
    $this->set('canonicalAddr', $newVal);
    $this->canonicalAddr = $newVal;
    return $this;
  }

  public function setContentClass(string $newVal) {
    $this->set('contentClass', $newVal);
    $this->contentClass = $newVal;
    return $this;
  }

  public function setContent(string $newVal) {
    $this->set('content', $newVal);
    $this->content = $newVal;
    return $this;
  }

  public function setContentType(string $newVal) {
    $this->set('contentType', $newVal);
    $this->contentType = $newVal;
    return $this;
  }

  public function setContentUri(Interfaces\Uri $newVal) {
    $this->set('contentUri', $newVal->toString());
    $this->contentUri = $newVal;
    return $this;
  }

  public function setDateCreated(\DateTime $newVal) {
    $this->set('dateCreated', $newVal->format(\DateTime::ISO8601));
    $this->dateCreated = $newVal;
    return $this;
  }

  public function setDateExpired(\DateTime $newVal) {
    $this->set('dateExpired', $newVal->format(\DateTime::ISO8601));
    $this->dateExpired = $newVal;
    return $this;
  }

  public function setDateUpdated(\DateTime $newVal) {
    $this->set('dateUpdated', $newVal->format(\DateTime::ISO8601));
    $this->dateUpdated = $newVal;
    return $this;
  }

  public function setLang(string $newVal) {
    $this->set('lang', $newVal);
    $this->lang = $newVal;
    return $this;
  }

  public function addAddress(string $newVal) {
    if (substr($newVal,0,1) != '/') $newVal = "/$newVal";
    $a = $this->addresses;
    if (array_search($newVal, $a) !== false) return $this;

    $a[] = $newVal;
    $this->set('addresses', $a);
    $this->addresses = $a;
    return $this;
  }

  public function removeAddress(string $val) {
    if (substr($val,0,1) != '/') $val = "/$val";
    if (($key = array_search($val, $this->addresses)) === false) return $this;
    array_splice($this->addresses, $key, 1);
    $this->set('addresses', $this->addresses);
    return $this;
  }

  public function addTag(string $newVal) {
    if (array_search($newVal, $this->tags) !== false) return $this;

    $this->tags[] = $newVal;
    $this->set('tags', $this->tags);
    return $this;
  }

  public function removeTag(string $val) {
    if (($key = array_search($val, $this->tags)) === false) return $this;
    array_splice($this->tags, $key, 1);
    $this->set('tags', $this->tags);
    return $this;
  }

  public function setTitle(string $newVal) {
    $this->set('title', $newVal);
    $this->title = $newVal;
    return $this;
  }




  public static function upgradeDatabase(int $targetVersion, int $fromVersion, Interfaces\Db $db) {
    /*
    if ($fromVersion < 1 && $targetVersion >= 1) {
      $db->exec('CREATE TABLE "'.\Skel\Content::CONTENT_TABLE_NAME.'" ('.implode(', '$this->getFields(\Skel\Content::CONTENT_TABLE_NAME)).')');
      $db->exec('CREATE TABLE "'.\Skel\Content::ALIAS_TABLE_NAME.'" ('.implode(', '$this->getFields(\Skel\Content::ALIAS_TABLE_NAME)).')');
      $db->exec('CREATE TABLE "'.\Skel\Content::ATTRS_TABLE_NAME.'" ('.implode(', '$this->getFields(\Skel\Content::ATTRS_TABLE_NAME)).')');
      $db->exec('CREATE TABLE "'.\Skel\Content::TAGS_TABLE_NAME.'" ('.implode(', '$this->getFields(\Skel\Content::TAGS_TABLE_NAME)).')');
    }
     */
  }

  public function undoChange(string $table, string $field) {
    $key = "$table.$field";
    if (!isset($this->changes[$key]) || count($this->changes[$key]) == 0) return false;
    $this->data[$key] = array_pop($this->changes[$key]);
    return true;
  }

  public function undoAll() {
    foreach($this->changes as $k => $v) {
      $this->data[$k] = $v[0];
      $this->changes[$k] = array();
    }
  }








  /****************************
   * Getters
   * *************************/

  public function getActive() { return $this->active; }
  public function getAddresses() { return $this->addresses; }
  public function getAttributes() { return $this->attributes; }
  public function getCanonicalAddr() { return $this->canonicalAddr; }
  public function getContent() { return $this->content; }
  public function getContentClass() { return $this->contentClass; }
  public function getContentType() { return $this->contentType; }
  public function getContentUri() { return $this->contentUri; }
  public function getDateCreated() { return $this->dateCreated; }
  public function getDateExpired() { return $this->dateExpired; }
  public function getDateUpdated() { return $this->dateUpdated; }
  public function getId() { return $this->id; }
  public function getLang() { return $this->lang; }
  public function getTags() { return $this->tags; }
  public function getTitle() { return $this->title; }
}


?>
