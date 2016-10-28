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
  protected $addresses = array();
  protected $attributes = array();
  protected $canonicalAddr;
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

  protected $cms;
  protected $rawData;
  protected $changes = array();
  protected $errors = array();

  public function __construct(Interfaces\CmsDb $cms, array $sourceData=null) {
    $this->cms = $cms;
    $defaults = array('active' => 1, 'addresses' => array(), 'attributes' => array(), 'canonicalAddr' => null, 'contentClass' => '\Skel\Content', 'contentType' => 'text/plain; charset=UTF-8', 'contentUri' => null, 'dateCreated' => (new \DateTime())->format(\DateTime::ISO8601), 'dateExpired' => null, 'dateUpdated' => (new \DateTime())->format(\DateTime::ISO8601), 'id' => null, 'lang' => null, 'tags' => array(), 'title' => null);

    // If building from data, make sure the source data is complete
    if ($sourceData) {
      $missing = array();
      foreach($defaults as $f => $v) {
        if (!array_key_exists($f, $sourceData)) $missing[] = $f;
      }
      if (count($missing) > 0) throw new InvalidDataException("Data passed into the Content constructor must have all of the fields required by Content. Missing fields: `".implode('`, `', $missing)."`.");
      $data = $sourceData;
    } else {
      $data = $defaults;
    }

    // Set fields
    $this->id = $data['id'];
    $this
      ->setActive((bool) $data['active'])
      ->setCanonicalAddr($data['canonicalAddr'])
      ->setContent($data['content'])
      ->setContentClass($data['contentClass'])
      ->setContentType($data['contentType'])
      ->setContentUri($data['contentUri'] ? new Uri($data['contentUri']) : null)
      ->setDateCreated($data['dateCreated'] ? \DateTime::createFromFormat(\DateTime::ISO8601, $data['dateCreated']) : null)
      ->setDateExpired($data['dateExpired'] ? \DateTime::createFromFormat(\DateTime::ISO8601, $data['dateExpired']) : null)
      ->setDateUpdated($data['dateUpdated'] ? \DateTime::createFromFormat(\DateTime::ISO8601, $data['dateUpdated']) : null)
      ->setLang($data['lang'])
      ->setTitle($data['title'])
    ;

    // Add lists
    foreach($data['addresses'] as $addr) $this->addAddress($addr);
    foreach($data['attributes'] as $k => $v) $this->setAttribute($k, $v);
    foreach($data['tags'] as $tag) $this->addTag($tag);

    // If we're building from data, consider this a fresh, unchanged object
    if ($sourceData) {
      $this->errors = array();
      $this->changed = array();
    }
  }




  public function save() {
    if (count($this->changes) === 0) return true;
    if (count($this->errors) > 0) throw new InvalidDataException("There are errors preventing this object from being saved. Please use `getErrors` on the Content object to show them to the user.");

    $data = array();
    foreach($this->changes as $field => $prevVal) $data[$field] = $this->rawData[$field];

    $id = $this->cms->saveContentData($this->id, $data);
    if (!$this->id) $this->id = $id;

    return $this;
  }









  /***************************
   * Setters
   * ************************/

  protected function set($field, $val) {
    if (!array_key_exists($field, $this->changes)) $this->changes[$field] = array();
    $this->changes[$field][] = $this->rawData[$field];
    $this->rawData[$field] = $val;
  }

  public function setActive($newVal) {
    if (!is_bool($newVal) && !is_numeric($newVal)) $this->setError('active', 'The Active flag must evaluate to true or false.');
    else $this->clearError('active');

    $this->set('active', (int) $newVal);
    $this->active = (bool)$newVal;
    return $this;
  }

  public function setAttribute(string $key, $newVal) {
    if (array_key_exists($key, $this->attributes) && $this->attributes[$key] == $newVal) return $this;

    $this->attributes[$key] = $newVal;
    $this->set('attributes', $this->attributes);
    return $this;
  }

  public function removeAttribute(string $key) {
    if (!array_key_exists($key, $this->attributes)) return $this;
    unset($this->attributes[$key]);
    $this->set('attributes', $this->attributes);
    return $this;
  }

  public function setCanonicalAddr(string $newVal=null) {
    if (!$newVal) $this->setError('canonicalAddr', 'The Canonical Address is required.');
    else $this->clearError('canonicalAddr');

    $this->set('canonicalAddr', $newVal);
    $this->canonicalAddr = $newVal;
    return $this;
  }

  public function setContentClass(string $newVal=null) {
    if (!$newVal) $this->setError('contentClass', 'You must specify a valid Content Class for this content.');
    else $this->clearError('contentClass');

    $this->set('contentClass', $newVal);
    $this->contentClass = $newVal;
    return $this;
  }

  public function setContent(string $newVal=null) {
    if (!$newVal) $this->setError('content', 'You\'ve gotta set some content.');
    else $this->clearError('content');

    $this->set('content', $newVal);
    $this->content = $newVal;
    return $this;
  }

  public function setContentType(string $newVal=null) {
    if (!$newVal) $this->setError('contentType', 'You must specifiy a valid Content Type for this content.');
    else $this->clearError('contentType');

    $this->set('contentType', $newVal);
    $this->contentType = $newVal;
    return $this;
  }

  public function setContentUri(Interfaces\Uri $newVal=null) {
    if (!$newVal) $this->setError('contentUri', 'You must specifiy a valid Content Uri for this content.');
    else $this->clearError('contentUri');

    $this->set('contentUri', ($newVal ? $newVal->toString() : null));
    $this->contentUri = $newVal;
    return $this;
  }

  public function setDateCreated(\DateTime $newVal=null) {
    if (!$newVal) $newVal = new \DateTime();
    $this->set('dateCreated', $newVal->format(\DateTime::ISO8601));
    $this->dateCreated = $newVal;
    return $this;
  }

  public function setDateExpired(\DateTime $newVal=null) {
    $this->set('dateExpired', ($newVal ? $newVal->format(\DateTime::ISO8601) : null));
    $this->dateExpired = $newVal;
    return $this;
  }

  public function setDateUpdated(\DateTime $newVal=null) {
    if (!$newVal) $newVal = new \DateTime();
    $this->set('dateUpdated', $newVal->format(\DateTime::ISO8601));
    $this->dateUpdated = $newVal;
    return $this;
  }

  public function setLang(string $newVal=null) {
    if (!$newVal || strlen($newVal) != 2) $this->setError('lang', 'You must specify a two-letter ISO 639-1 language code for your content.');
    else $this->clearError('lang');

    $this->set('lang', $newVal);
    $this->lang = $newVal;
    return $this;
  }

  public function addAddress(string $newVal) {
    if (substr($newVal,0,1) != '/') $newVal = "/$newVal";
    $a = $this->addresses;

    if (!$this->canonicalAddr) $this->setCanonicalAddr($newVal);
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

  public function setTitle(string $newVal=null) {
    if (!$newVal) $this->setError('title', 'You must specifiy a valid Title for this content.');
    else $this->clearError('title');

    $this->set('title', $newVal);
    $this->title = $newVal;
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








  /****************************
   * Getters
   * *************************/

  public function getActive() { return $this->active; }
  public function getAddresses() { return $this->addresses; }
  public function getAttribute(string $key, $defaultValue=null) {
    if (array_key_exists($key, $this->attributes)) return $this->attributes[$key];
    else return $defaultValue;
  }
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
