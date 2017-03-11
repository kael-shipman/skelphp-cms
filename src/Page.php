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
  const TABLE_NAME = 'content';

  protected $cms;

  public function __construct(array $elements=array(), Interfaces\Template $t=null) {
    $this->addDefinedFields(array('active','address','canonicalId','content','contentClass','dateCreated','dateExpired','dateUpdated','lang','title','hasImg','imgPrefix'));
    $this
      ->set('active', true, true)
      ->set('contentClass', static::getNormalizedClassName(), true)
      ->set('dateCreated', new \DateTime(), true)
      ->set('dateUpdated', new \DateTime(), true)
      ->set('hasImg', false, true)
      ->set('lang', 'en', true)
    ;
    $this->registerListener('Change', $this, 'onFieldChange');
    parent::__construct($elements, $t);
  }

  public function updateFromUserInput(array $data) {
    parent::updateFromUserInput($data);
    if (array_key_exists('tags', $data)) $this['tags'] = $data['tags'];
    return $this;
  }

  public function setDb(Interfaces\Cms $cms) {
    $this->cms = $cms;
    return $this;
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









  // Getters and Attributes

  public function getAddress() { return $this['address']; }

  public function getAncestors() {
    $path = explode('/', trim($this->getParentAddress(), '/'));
    $ancestors = array();
    foreach($path as $k => $p) $ancestors[] = $ancestors[$k-1].'/'.$p;
    return $ancestors;
  }

  public function getCanonicalId() { return $this['canonicalId']; }

  public function getChildren(Interfaces\Cms $cms=null) {
    if (!$cms) $cms = $this->cms;
    if (!$cms) throw new UnpreparedObjectException("`getChildren` is a lazy-loader and requires a Skel\Interfaces\Cms object. This object may be passed into the function itself or may be provided beforehand via the Page object's `setDb` method");
    return new DataCollection($cms->getContentIndex(array($this['address'])));
  }

  public function getContent() { return $this['content']; }

  public function getContentClass() { return $this['contentClass']; }

  public function getDateCreated() { return $this['dateCreated']; }

  public function getDateExpired() { return $this['dateExpired']; }

  public function getDateUpdated() { return $this['dateUpdated']; }

  public function getImgPrefix() { return $this['imgPrefix']; }

  public function getLang() { return $this['lang']; }

  public function getParentAddress() {
    $a = $this['address'];
    return substr($a, 0, strrpos($a, '/'));
  }

  public function getParentCanonicalId() {
    $id = $this['canonicalId'];
    return substr($id, 0, strrpos($id, '/'));
  }

  public function getSlug() {
    $a = $this['address'];
    return substr($a, strrpos($a, '/')+1);
  }

  public function getTags(Interfaces\Cms $cms=null) {
    if (!$cms) $cms = $this->cms;
    if (!$cms) throw new UnpreparedObjectException("`getTags` is a lazy-loader and requires a Skel\Interfaces\Cms object. This object may be passed into the function itself or may be provided beforehand via the Page object's `setDb` method");
    if (!$this->offsetExists('tags')) $this['tags'] = $this->cms->getContentTags($this);
    return $this->elements['tags'];
  }

  public function getTitle() { return $this['title']; }

  public function hasChildren() { return count($this->getChildren()) > 0; }

  public function hasImg() { return $this->get('hasImg'); }

  public function isActive() { return (bool) $this['active']; }








  // Overrides

  protected function convertDataToField(string $field, $dataVal) {
    if ($dataVal === null) return $dataVal;

    if ($field == 'active' || $field == 'hasImg') return (bool)$dataVal;
    if (substr($field, 0, 4) == 'date') {
      if (!($newVal = \DateTime::createFromFormat(\DateTime::ISO8601, $dataVal))) {
        if (strlen($dataVal) == 10) $newVal = preg_replace('/[_\/.\']/', '-', $dataVal).'T'.(new \DateTime())->format('H:i:sO');
        else $newVal = $dataVal;

        if (!($newVal = \DateTime::createFromFormat(\DateTime::ISO8601, $newVal))) throw new \InvalidArgumentException("Don't know how to convert `$dataVal` to a valid DateTime object! Dates should be passed as either ISO8601 format (yyyy-mm-ddThh:mm:ss-zzzz) or as simply yyyy-mm-dd.");
      }
      return $newVal;
    }
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
      'acive' => 'You must specify whether or not this content is actively visible. This field cannot be null.',
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

    if ($field == 'lang') {
      if (!is_string($val) || strlen($val) != 2) $this->setError($field, 'You must specify a two-letter ISO 639-1 language code for your page.', 'value');
      else $this->clearError($field, 'value');
    } elseif ($field == 'contentClass') {
      if ($val != static::getNormalizedClassName()) $this->setError($field, "The classname you've specified is inconsistent with the current Object you're using!", 'value');
      else $this->clearError($field, 'value');
    }
  }

  public function validateObject(Interfaces\Db $cms=null) {
    if (!$cms) $cms = $this->cms;
    // Validate Address
    if (!$cms->contentAddressIsUnique($this)) {
      $this->setError('address', 'The address you specified for this content ('.$this['address'].') is already being used by other content.', 'uniqueness');
    } else {
      $this->clearError('address', 'uniqueness');
    }

    // Validate uniqueness of canonicalId + Lang
    if (!$cms->contentCanonicalIdIsUnique($this)) {
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
