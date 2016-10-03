<?php
namespace Skel;


/*
get_title()
get_meta_tags()
get_stylesheets()
get_canonical_id()
get_canonical_class_names()
router->get_route_for('site_header_image')
strings('org_name')
get_controls('Subprime Menu')
get_language_links()
get_content()
get_content_by_canonical_name('contact')->get_display_name()
get_javascripts()
*/




/**
 * Basic content class for Skel framework
 *
 * This class is sufficient for the most basic text-based content. It will generally
 * be extended.
 *
 * @known_direct_subclasses Page
 * */
class Content implements Interfaces\Persistible {
  protected $id;
  protected $displayName;
  protected $content;
  protected $template;
  protected $lang;
  protected $createdDate;
  protected $updatedDate;
  protected $visible;
  protected $searchableFields = array('displayName', 'content');
  protected $changedFields = array();

  protected function __construct() {
  }

  /**
   * Restores a Content object from serialized data in db using the object's unique ID
   *
   * @return Content
   * @param DB $db - The data object from which to retrieve the data
   * @param int $id - The unique id of the object being retrieved from the database
   */
  public static function createFromData(Interfaces\DB $db, $id) {
    $content = new Content($db);
    $data = $content->db->getContentById($id);
    //TODO: Deserialize data into object
    //TODO: Figure out how to unserialize user-specified fields
    return $content;
  }

  public function persist(Interfaces\DB $db) {
    //TODO: Implement persistent storage
  }







  /***************************
   * Setters
   * ************************/

  /**
   * Sets the content's displayName;
   *
   * @param string $newValue - A pretty name that shows up in CMS menus
   * */
  public function setDisplayName(string $newValue) {
    $db->setValue('displayName', $newValue);
    $this->displayName = $newValue;
  }

  /**
   * Sets the content's content
   *
   * @param string $newValue - This is the user-visible stuff
   * */
  public function setContent(string $newValue) {
    $db->setValue('content',$newValue);
    $this->content = $newValue;
  }

  /**
   * Sets the content's template
   *
   * @param Template $newValue - A new template to use to render this content (can be blank)
   * */
  public function setTemplate(Template $newValue) {
    $this->db->setValue('template', $newValue->serialize());
    $this->template = $newValue;
  }

  /**
   * Sets the content's Language
   *
   * @param Lang $newValue
   * */
  public function setLang(Lang $newValue) {
    $this->db->setValue('lang', $newValue->getLangCode());
    $this->lang = $newValue;
  }

  /**
   * Sets the content's Created Date
   *
   * @param DateTime $newValue
   * */
  public function setCreatedDate(DateTime $newValue) {
    $this->db->setValue('createdDate', $newValue->format(DateTime::ISO8601));
    $this->createdDate = $newValue;
  }










  /****************************
   * Getters
   * *************************/

  /**
   * Returns the content's unique ID
   *
   * @return int
   * */
  public function getId() { return $this->id; }

  /**
   * Returns the content's display name
   *
   * @return string
   * */
  public function getDisplayName() { return $this->displayName; }

  /**
   * Returns the content's content (i.e., the stuff intended for the user to see)
   *
   * @return string
   * */
  public function getContent() { return $this->content; }

  /**
   * Returns the content's language object
   *
   * @return SkelLang
   * */
  public function getLang() { return $this->lang; }

  /** Returns the content's Created date
   *
   * @return DateTime
   * */
  public function getCreatedDate() { return $this->createdDate; }

  /**
   * Returns the content's Updated date
   *
   * @return DateTime
   * */
  public function getUpdatedDate() { return $this->updatedDate; }

  /**
   * Returns the content's visiblity
   *
   * @return boolean
   * */
  public function getVisibility() { return $this->visibility; }

  /**
   * Returns all of the searchable fields this class registers
   *
   * This can be used to index certain fields for efficient search. Subclasses should
   * call superclass methods to ensure that all fields are registered
   *
   * @return array
   * */
  public function getSearchableFields() {
    return $this->searchableFields;
  }
}


?>
