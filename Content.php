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
class Content implements Interfaces\Content {
  protected $active;
  protected $address;
  protected $canonicalId;
  protected $content;
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

  protected $rawData;
  protected $changes = array();
  protected $errors = array();

  public function __construct(array $data=array()) {

    $this
      ->setActive((bool) $data['active'])
      ->setAddress($data['address'])
      ->setCanonicalId($data['canonicalId'])
      ->setContent($data['content'])
      ->setContentClass($data['contentClass'] ?: $this->getNormalizedClassName())
      ->setContentType($data['contentType'])
      ->setContentUri($data['contentUri'] ? new Uri($data['contentUri']) : null)
      ->setDateCreated($data['dateCreated'] ? \DateTime::createFromFormat(\DateTime::ISO8601, $data['dateCreated']) : null)
      ->setDateExpired($data['dateExpired'] ? \DateTime::createFromFormat(\DateTime::ISO8601, $data['dateExpired']) : null)
      ->setDateUpdated($data['dateUpdated'] ? \DateTime::createFromFormat(\DateTime::ISO8601, $data['dateUpdated']) : null)
      ->setId($data['id'])
      ->setLang($data['lang'])
      ->setTitle($data['title'])
    ;

    // Add tags
    foreach($data['tags'] as $tag) $this->addTag($tag);

    // If we're building from data, consider this a fresh, unchanged object
    if (count($data) > 0) $this->changes = array();
  }

  public static function createSlug(string $str) {
    $str = strtolower($str);
    $str = str_replace(array('—', '–', ' - ', ' -- ', ' '), '-', $str);
    //TODO: Complete this list of common foreign special chars
    $str = str_replace(array('á','é','í','ó','ú','ñ'), array('a','e','i','o','u','n'), $str);
    $str = preg_replace('/[^a-zA-Z0-9_-]/', '', $str);
    return $str;
  }










  /***************************
   * Setters
   * ************************/

  protected function setData($field, $val) {
    if (!array_key_exists($field, $this->changes)) $this->changes[$field] = array();
    $this->changes[$field][] = $this->rawData[$field];
    $this->rawData[$field] = $val;
  }

  public function setActive(bool $newVal=true) {
    $this->setData('active', (int) $newVal);
    $this->active = (bool)$newVal;
    $this->validate('active');
    return $this;
  }

  public function setAddress(string $newVal=null) {
    $this->setData('address', $newVal);
    $this->address = $newVal;
    $this->validate('address');
    return $this;
  }

  public function setCanonicalId(string $newVal=null) {
    $this->setData('canonicalId', $newVal);
    $this->canonicalAddr = $newVal;
    $this->validate('canonicalId');
    return $this;
  }

  public function setContent(string $newVal=null) {
    $this->setData('content', $newVal);
    $this->content = $newVal;
    $this->validate('content');
    return $this;
  }

  public function setContentClass(string $newVal=null) {
    $this->setData('contentClass', $newVal);
    $this->contentClass = $newVal;
    $this->validate('contentClass');
    return $this;
  }

  public function setContentType(string $newVal='text/plain; charset=UTF-8') {
    $this->setData('contentType', $newVal);
    $this->contentType = $newVal;
    $this->validate('contentType');
    return $this;
  }

  public function setContentUri(Interfaces\Uri $newVal=null) {
    $this->setData('contentUri', ($newVal ? $newVal->toString() : null));
    $this->contentUri = $newVal;
    $this->validate('contentUri');
    return $this;
  }

  public function setDateCreated(\DateTime $newVal=null) {
    if (!$newVal) $newVal = new \DateTime();
    $this->setData('dateCreated', $newVal->format(\DateTime::ISO8601));
    $this->dateCreated = $newVal;
    $this->validate('dateCreated');
    return $this;
  }

  public function setDateExpired(\DateTime $newVal=null) {
    $this->setData('dateExpired', ($newVal ? $newVal->format(\DateTime::ISO8601) : null));
    $this->dateExpired = $newVal;
    $this->validate('dateExpired');
    return $this;
  }

  public function setDateUpdated(\DateTime $newVal=null) {
    if (!$newVal) $newVal = new \DateTime();
    $this->setData('dateUpdated', $newVal->format(\DateTime::ISO8601));
    $this->dateUpdated = $newVal;
    $this->validate('dateUpdated');
    return $this;
  }

  public function setId(int $newVal) {
    if ($this->id && $newVal != $this->id) throw new InvalidDataException("You can't change the id once it's already set!");
    $this->id = $newVal;
  }

  public function setLang(string $newVal=null) {
    $this->setData('lang', $newVal);
    $this->lang = $newVal;
    $this->validate('lang');
    return $this;
  }

  public function addTag(string $newVal) {
    if (array_search($newVal, $this->tags) !== false) return $this;

    $this->tags[] = $newVal;
    $this->setData('tags', $this->tags);
    return $this;
  }

  public function removeTag(string $val) {
    if (($key = array_search($val, $this->tags)) === false) return $this;
    array_splice($this->tags, $key, 1);
    $this->setData('tags', $this->tags);
    return $this;
  }

  public function setTitle(string $newVal=null) {
    $this->setData('title', $newVal);
    $this->title = $newVal;
    $this->validate('title');
    return $this;
  }






  /********************
   * Errors
   * *****************/

  public function setError(string $key, string $val) {
    $this->errors[$key] = $val;
  }
  public function clearError(string $key) {
    unset($this->errors[$key]);
  }
  public function getErrors() { return $this->errors; }





  public function undoChange(string $table, string $field) {
    $key = "$table.$field";
    if (!array_key_exists($key, $this->changes) || count($this->changes[$key]) == 0) return false;
    $this->data[$key] = array_pop($this->changes[$key]);
    return true;
  }

  public function undoAll() {
    foreach($this->changes as $k => $v) {
      $this->data[$k] = $v[0];
      $this->changes[$k] = array();
    }
  }

  protected function validate(string $field) {
    $val = $this->$field;
    $error = null;
    $required = array(
      'canonicalId' => 'The Canonical Id is required.',
      'contentClass' => 'You must specify a valid Content Class for this content.',
      'content' => 'You\'ve gotta set some content.',
      'contentType' => 'You must specifiy a valid Content Type for this content.',
      'contentUri' => 'You must specifiy a valid Content Uri for this content.',
      'title' => 'You must specifiy a valid Title for this content.',
    );

    if (array_key_exists($field, $required) && !$val) $error = $required[$field];

    if ($field == 'active') {
      if (!is_bool($val)) $error = 'Active cannot be null. It must be either true or false.';
    } elseif ($field == 'lang') {
      if (!is_string($val) || strlen($val) != 2) $error = 'You must specify a two-letter ISO 639-1 language code for your content.';
    } elseif ($field == 'contentClass') {
      if ($val != $this->getNormalizedClassName()) $error = "The classname you've specified is inconsistent with the current Object you're using!";
    }

    if ($error) {
      $this->setError($field, $error);
      return false;
    } else {
      $this->clearError($field);
      return true;
    }
  }
      








  /****************************
   * Getters
   * *************************/

  public function getActive() { return $this->active; }
  public function getAddress() { return $this->address; }
  public function getCanonicalId() { return $this->canonicalId; }
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

  public function getNormalizedClassName() {
    $str = preg_replace(array('/([A-Z])/', '/_-/'), array('-\1','_'), static::class);
    return trim(strtolower($str), '-');
  }
    
  public function getChanges() {
    $changes = array();
    foreach($this->changes as $k => $prev) $changes[$k] = $this->rawData[$k];
    return $changes;
  }
  public function getRawData() { return $this->rawData; }
}


?>
