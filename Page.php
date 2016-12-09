<?php
namespace Skel;

/**
 * Basic Page class for Skel framework
 *
 * This class is sufficient for the most basic site pages. It may be extended
 * to accommodate other types, like blog posts and more complex pages.
 *
 * */
class Page extends DataClass implements Interfaces\Page, Interfaces\Observable {
  use ObservableTrait;

  const TABLE_NAME = 'content';

  protected $db;

  public function __construct(array $elements=array(), Interfaces\Template $t=null) {
    parent::__construct($elements, $t);
    $this->addDefinedFields(array('active','address','canonicalId','content','contentClass','dateCreated','dateExpired','dateUpdated','lang','title','hasImg','imgPrefix'));
    $this
      ->set('active', true, true)
      ->set('contentClass', static::getNormalizedClassName(), true)
      ->set('dateCreated', new \DateTime(), true)
      ->set('dateUpdated', new \DateTime(), true)
      ->set('lang', 'en', true)
    ;
    $this->registerListener('Change', $this, 'onFieldChange');
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

  public static function createSlug(string $str) {
    $str = strtolower($str);
    $str = str_replace(array('—', '–', ' - ', ' -- ', ' '), '-', $str);
    //TODO: Complete this list of common foreign special chars
    $str = str_replace(array('á','é','í','ó','ú','ñ'), array('a','e','i','o','u','n'), $str);
    $str = preg_replace('/[^a-zA-Z0-9_-]/', '', $str);
    return $str;
  }

  public function addTag(string $newVal) {
    if (array_search($newVal, $this->tags) !== false) return $this;
    $this->tags[] = $newVal;
    $this->setData('tags', $this->tags, false);
    return $this;
  }

  public function getAncestors() {
    $path = explode('/', trim($this->getParentAddress(), '/'));
    $section = array_shift($path);
    $a = array();
    $ancestors = array();
    foreach($path as $p) {
      $a[] = $p;
      $ancestors[] = '/'.implode('/',$a);
    }
    return $ancestors;
  }

  public function getParentAddress() {
    $a = $this['address'];
    return substr($a, 0, strrpos($a, '/'));
  }

  public function getTags() {
    if (!$this['tags']) $this['tags'] = $this->db->getContentTags($this);
  }

  public function hasImg() { return $this->get('hasImg'); }

  public function removeTag(string $val) {
    if (($key = array_search($val, $this->tags)) === false) return $this;
    array_splice($this->tags, $key, 1);
    $this->setData('tags', $this->tags, false);
    return $this;
  }

  public function setDb(Interfaces\CmsDb $db) {
    $this->db = $db;
    return $this;
  }








  // Overrides

  protected function convertDataToField(string $field, $dataVal) {
    if ($field == 'active' || $field == 'hasImg') return (bool)$dataVal;
    if (substr($field, 0, 4) == 'date' && $dataVal !== null) return \DateTime::createFromFormat(\DateTime::ISO8601, $dataVal);
    return parent::convertDataToField($field, $dataVal);
  }

  protected function onFieldChange(Interfaces\DataClass $dataclass, string $field, $oldVal, $newVal) {
    if ($field == 'content') {
      if (!$this->fieldSetBySystem('content')) $this->set('dateUpdated', new \DateTime(), true);
    }
    if ($field == 'address') {
      if ($this->fieldSetBySystem('canonicalId')) $this->set('canonicalId', $newVal, true);
    }
    if ($field == 'title') {
      if ($this->fieldSetBySystem('address') && $newVal) $this->set('address', $this->getParentAddress().'/'.static::createSlug($newVal), true);
    }
    if ($field == 'dateCreated' || $field == 'title') {
      if ($this->fieldSetBySystem('imgPrefix') && ($title = $this['title']) && ($date = $this['dateCreated'])) $this->set('imgPrefix', static::createImgPrefix($date, $title), true);
    }
  }

  protected function typecheckAndConvertInput(string $field, $val) {
    if ($val === null || $val instanceof DataCollection) return $val;

    if ($field == 'active' || $field == 'hasImg') {
      if (!is_bool($val)) throw new \InvalidArgumentException("Field `$field` must be a boolean value!");
      return (int)$val;
    }
    if ($field == 'address' || $field == 'canonicalId' || $field == 'content' || $field == 'contentClass' || $field == 'lang' || $field == 'title' || $field == 'imgPrefix') {
      if (!is_string($val)) throw new \InvalidArgumentException("Field `$field` must be a string!");
      if ($field == 'lang' && strlen($val) != 2) throw new \InvalidArgumentException("Field `$field` must be a two-digit ISO language code!");
      return $val;
    }
    if (substr($field, 0, 4) == 'date') {
      if (!($val instanceof \DateTime)) throw new \InvalidArgumentException("Field `$field` must be a valid DateTime object!");
      return $val->format(\DateTime::ISO8601);
    }
    return parent::typecheckAndConvertInput($field, $val);
  }

  protected function validateField(string $field) {
    $val = $this->get($field);
    $required = array(
      'address' => 'You must specify a public address at which this page can be found',
      'canonicalId' => 'The Canonical Id is required.',
      'content' => 'You must create content for this page.',
      'contentClass' => 'You must specify a valid Content Class for this page. (This is usually done automatically, so there may be an error in the code.)',
      'title' => 'You must specifiy a valid Title for this page.',
      'hasImg' => 'All posts must have the `hasImg` flag set to true or false',
      'imgPrefix' => 'All posts must have an imgPrefix, even if they don\'t have any images',
    );

    if (array_key_exists($field, $required) && ($val === null || $val === '')) $this->setError($field, $required[$field], 'required');
    else $this->clearError($field, 'required');

    if ($field == 'active' || $field == 'hasImg') {
      if (!is_bool($val)) $this->setError($field, "Field `$field` cannot be null. It must be either true or false.", 'value');
      else $this->clearError($field, 'value');
    } elseif ($field == 'lang') {
      if (!is_string($val) || strlen($val) != 2) $this->setError($field, 'You must specify a two-letter ISO 639-1 language code for your page.', 'value');
      else $this->clearError($field, 'value');
    } elseif ($field == 'contentClass') {
      if ($val != static::getNormalizedClassName()) $this->setError($field, "The classname you've specified is inconsistent with the current Object you're using!", 'value');
      else $this->clearError($field, 'value');
    }
  }

  public function validateObject() {
    // Validate Address
    if (!$this->db->contentAddressIsUnique($this)) {
      $this->setError('address', 'The address you specified for this content ('.$this['address'].') is already being used by other content.', 'uniqueness');
    } else {
      $this->clearError('address', 'uniqueness');
    }

    // Validate uniqueness of canonicalId + Lang
    if ($this->db->contentCanonicalIdIsUnique($this)) {
      $this->setError('canonicalId', 'The canonical Id you specified for this content ('.$this['canonicalId'].') is already being used by other content with the same language ('.$this['lang'].'). You must either change the language or change the canonical Id.', 'uniqueness');
    } else {
      $this->clearError('canonicalId', 'uniqueness');
    }
  }





  public function offsetGet($key) {
    if ($key == 'tags') return $this->getTags();
    else return parent::offsetGet($key);
  }
}


?>
