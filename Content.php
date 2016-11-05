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
  protected $parentAddress;
  protected $slug;
  protected $tags = array();
  protected $title;

  protected $changes = array();
  protected $errors = array();
  protected $setBySystem = array();

  protected static $primaryFields = array('active', 'canonicalId', 'content', 'contentClass', 'contentType', 'contentUri', 'dateCreated', 'dateExpired', 'dateUpdated', 'id', 'lang', 'parentAddress', 'slug', 'title');
  protected static $__primaryFields;

  public function __construct(array $data=array()) {
    if (count($data) > 0) $this->loadFromData($data);
    else $this->loadDefaults();
  }

  protected function loadFromData(array $data) {
    $fields = array('active', 'canonicalId', 'content', 'contentClass', 'contentType', 'contentUri', 'dateCreated', 'dateExpired', 'dateUpdated', 'id', 'lang', 'parentAddress', 'setBySystem', 'slug', 'tags', 'title');
    foreach ($fields as $field) {
      if (!array_key_exists($field, $data)) throw new InvalidDataException("Required field `$field` isn't set in the data to load into Content.");

      if ($field == 'active') $val = (bool) $data[$field];
      elseif ($field == 'contentUri') $val = new Uri($data[$field]);
      elseif (substr($field,0,4) == 'date') $val = \DateTime::createFromFormat(\DateTime::ISO8601, $data[$field]);
      elseif ($field == 'id') $val = (int) $data[$field];
      elseif ($field == 'setBySystem') {
        foreach($data[$field] as $f) $this->setBySystem[$f] = true;
        continue;
      } elseif ($field == 'tags') {
        foreach($data[$field] as $tag) $this->tags[] = $tag;
        continue;
      }
      else $val = $data[$field];

      $this->$field = $val;
    }
  }

  protected function loadDefaults() {
    $this
      ->setActive()
      ->setCanonicalId()
      ->setContent()
      ->setContentClass()
      ->setContentType()
      ->setContentUri()
      ->setDateCreated()
      ->setDateExpired()
      ->setDateUpdated()
      ->setLang()
      ->setParentAddress()
      ->setSlug()
      ->setTitle()
    ;
    $this->setBySystem = array('active' => true, 'canonicalId' => true, 'content' => true, 'contentClass' => true, 'contentType' => true, 'contentUri' => true, 'dateCreated' => true, 'dateExpired' => true, 'dateUpdated' => true, 'id' => true, 'lang' => true, 'parentAddress' => true, 'slug' => true, 'title' => true);
  }

 
  public static function createContentUri(string $address=null) {
    return new Uri('file://pages'.$address.'.md');
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

  protected function setData($field, $val, $setBySystem) {
    if (!array_key_exists($field, $this->changes)) $this->changes[$field] = array();
    $this->changes[$field][] = $this->$field;
    $this->$field = $val;
    $this->setBySystem[$field] = $setBySystem;
    $this->validate($field);
    $this->fireEvent($field);
  }

  public function setActive(bool $newVal=true, bool $setBySystem=false) {
    $this->setData('active', $newVal, $setBySystem);
    return $this;
  }

  public function setCanonicalId(string $newVal=null, bool $setBySystem=false) {
    $this->setData('canonicalId', $newVal, $setBySystem);
    return $this;
  }

  public function setContent(string $newVal=null, bool $setBySystem=false) {
    $this->setData('content', $newVal, $setBySystem);
    return $this;
  }

  protected function setContentClass(string $newVal=null, bool $setBySystem=false) {
    if (!$newVal) $newVal = static::getNormalizedClassName();
    $this->setData('contentClass', $newVal, $setBySystem);
    return $this;
  }

  public function setContentType(string $newVal='text/plain; charset=UTF-8', bool $setBySystem=false) {
    $this->setData('contentType', $newVal, $setBySystem);
    return $this;
  }

  public function setContentUri(Interfaces\Uri $newVal=null, bool $setBySystem=false) {
    $this->setData('contentUri', $newVal, $setBySystem);
    return $this;
  }

  public function setDateCreated(\DateTime $newVal=null, bool $setBySystem=false) {
    if (!$newVal) $newVal = new \DateTime();
    $this->setData('dateCreated', $newVal, $setBySystem);
    return $this;
  }

  public function setDateExpired(\DateTime $newVal=null, bool $setBySystem=false) {
    $this->setData('dateExpired', $newVal, $setBySystem);
    return $this;
  }

  public function setDateUpdated(\DateTime $newVal=null, bool $setBySystem=false) {
    // Don't overwrite a valid date with null
    if (!$newVal) {
      if ($this->dateUpdated) return $this;
      // If not yet set, set to today
      if (!$newVal) $newVal = new \DateTime();
    }

    $this->setData('dateUpdated', $newVal, $setBySystem);
    return $this;
  }

  public function setId(int $newVal, bool $setBySystem=false) {
    if ($this->id && $newVal != $this->id) throw new InvalidDataException("You can't change the id once it's already set!");
    $this->id = $newVal;
    return $this;
  }

  public function setLang(string $newVal='en', bool $setBySystem=false) {
    $this->setData('lang', $newVal, $setBySystem);
    return $this;
  }

  public function setParentAddress(string $newVal=null, bool $setBySystem=false) {
    if ($newVal && substr($newVal, -1) == '/') $newVal = substr($newVal, 0, -1);
    $this->setData('parentAddress', $newVal, $setBySystem);
    return $this;
  }

  public function setSlug(string $newVal=null, bool $setBySystem=false) {
    if ($newVal) $newVal = trim($newVal, '/');
    $this->setData('slug', $newVal, $setBySystem);
    return $this;
  }

  public function addTag(string $newVal) {
    if (array_search($newVal, $this->tags) !== false) return $this;
    $this->tags[] = $newVal;
    $this->setData('tags', $this->tags, false);
    return $this;
  }

  public function removeTag(string $val) {
    if (($key = array_search($val, $this->tags)) === false) return $this;
    array_splice($this->tags, $key, 1);
    $this->setData('tags', $this->tags, false);
    return $this;
  }

  public function setTitle(string $newVal=null, bool $setBySystem=false) {
    $this->setData('title', $newVal, $setBySystem);
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
      'slug' => 'You must supply a "slug", or "pretty url" for your content. Note that this should not have slashes in it.',
      'title' => 'You must specifiy a valid Title for this content.',
    );

    if (array_key_exists($field, $required) && !$val) $error = $required[$field];

    if ($field == 'active') {
      if (!is_bool($val)) $error = 'Active cannot be null. It must be either true or false.';
    } elseif ($field == 'lang') {
      if (!is_string($val) || strlen($val) != 2) $error = 'You must specify a two-letter ISO 639-1 language code for your content.';
    } elseif ($field == 'contentClass') {
      if ($val != $this->getNormalizedClassName()) $error = "The classname you've specified is inconsistent with the current Object you're using!";
    } elseif ($field == 'slug') {
      if (strpos($val, '/') !== false) $error = 'The "slug" or "pretty url" cannot have slashes in it (it should only be the last part of the url, the part that specifically identifies this content).';
    }

    if ($error) {
      $this->setError($field, $error);
      return false;
    } else {
      $this->clearError($field);
      return true;
    }
  }



  protected function fireEvent(string $field) {
    if ($field == 'content') {
      if (!$this->fieldSetBySystem('content')) $this->setDateUpdated(new \DateTime(), true);
    }
    if ($field == 'parentAddress' || $field == 'slug') {
      if ($this->fieldSetBySystem('canonicalId') && $this->slug) $this->setCanonicalId($this->getAddress(), true);
      if ($this->fieldSetBySystem('contentUri')) $this->setContentUri(static::createContentUri($this->getAddress()), true);
    }
    if ($field == 'title') {
      if ($this->fieldSetBySystem('slug') && $this->title) $this->setSlug(static::createSlug($this->$field), true);
    }
  }








  /****************************
   * Getters
   * *************************/

  public function getActive() { return $this->active; }
  public function getAddress() {
    if (!$this->slug) return null;
    return $this->parentAddress.'/'.$this->slug;
  }
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
  public function getParentAddress() { return $this->parentAddress; }
  public function getSlug() { return $this->slug; }
  public function getTags() { return $this->tags; }
  public function getTitle() { return $this->title; }

  public static function getNormalizedClassName() {
    $str = explode('\\', static::class);
    $str = array_pop($str);
    $str = preg_replace(array('/([A-Z])/', '/_-/'), array('-\1','_'), $str);
    return trim(strtolower($str), '-');
  }
    
  public function getChanges() {
    $changes = array();
    foreach($this->changes as $k => $prev) $changes[$k] = $this->$k;
    return $changes;
  }
  public function fieldSetBySystem(string $field) { return (bool) $this->setBySystem[$field]; }
  public function getFieldsSetBySystem() {
    $fields = array();
    foreach($this->setBySystem as $field => $set) {
      if ($set) $fields[] = $field;
    }
    return $fields;
  }

  public function getPrimaryFields() {
    if (static::$__primaryFields) return static::$__primaryFields;

    $fields = static::$primaryFields;
    $parent = static::class;
    while ($parent = get_parent_class($parent)) {
      try {
        $parentFields = $parent::$primaryFields;
      } catch (\Throwable $e) {
        $parentFields = array();
      }
      $fields = array_merge($fields, $parentFields);
    }
    static::$__primaryFields = $fields;

    return static::$__primaryFields;
  }
}


?>
