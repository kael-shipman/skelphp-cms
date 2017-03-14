<?php
/**
 * Cms is a base Cms class that manages database interactions and data interfacing
 *
 * NOTE: This documentation is out of date. Please disregard until further notice.
 *
 * This is a tricky class. The concept is that name of the content classes are stored
 * in the db as part of the object data. Therefore, the actual Cms object that you use must
 * know how to handle all of the classes of content that your app uses. This is accomplished
 * (theoretically) by overriding six methods in all descendent Cms classes. Importantly, any
 * of these six methods may throw an `UnknownContentClassException` when it encounters content
 * classes it doesn't explicitly know how to handle. Thus, when you override these methods,
 * you can first call `parent` to save the basic content data, then test for all the classes
 * you've added with your Cms derivative that need special attention. If an
 * `UnknownContentClassException` is thrown, that means that the given class couldn't be handled
 * and should be handled explicitly by your version of the method. (This means you should always
 * wrap calls to `parent::*` in a try block that catches the `UnknownContentClassException`.)
 *
 * The six methods are:
 *
 * * `validateContent` - Does what it says -- called from `saveContent`
 * * `saveContent` - Performs the actual mechanics of saving after validation
 * * `saveExtraField` - Used to handle fields that may not be in the "content" table
 * * `deleteContent` - Can be overridden to delete additional records or resources related to the content
 * * `addAuxData` - called from all of the `getContent*` methods and used to augment the returned array with the correct fields for each content class
 * * `dressData` - used to create Data Objects based on content class names in data array
 */
namespace Skel;

class Cms extends Db implements Interfaces\Cms {

  const VERSION = 1;
  const SCHEMA_NAME = "SkelCms";









  /****************************
   * Public Content Methods
   * *************************/

  public function getContentByAddress($val) {
    if (!is_array($val)) {
      $single = true;
      $val = array($val);
    } else {
      $single = false;
    }
    $placeholders = array();
    for($i=0; $i < count($val); $i++) $placeholders[] = '?';

    $stm = $this->db->prepare('SELECT * FROM "content" WHERE "active" = 1 and "address" in ('.implode(',', $placeholders).')');
    $stm->execute($val);
    $content = $this->getObjectsFromQuery($stm);

    if (!$single) return $content;
    else {
      if (count($content) > 0) return $content[0];
      else return null;
    }
  }

  public function getContentByCanonicalId(string $canonicalId, string $lang='en') {
    $stm = $this->db->prepare('SELECT * FROM "content" WHERE "active" = 1 and "lang" = ? and "canonicalId" = ?');
    $stm->execute(array($lang, $canonicalId));
    $content = $this->getObjectsFromQuery($stm);
    if (count($content) > 0) return $content[0];
    return null;
  }

  public function getContentById(int $id) {
    $stm = $this->db->prepare('SELECT * FROM "content" WHERE "id" = ?');
    $stm->execute(array($id));
    $content = $this->getObjectsFromQuery($stm);
    if (count($content) > 0) return $content[0];
    return null;
  }

  public function getContentIndex(array $parent_addresses=null, int $limit=null, int $offset=0, $orderby=null) {
    if (!$orderby) $orderby = '"dateCreated" DESC';
    $orderby = 'ORDER BY '.$orderby.' ';
    if ($limit) $limit = "LIMIT $limit OFFSET $offset";

    if (!$parent_addresses) $parent_addresses = array();
    $placeholders = array();
    foreach($parent_addresses as $k => $p) {
      $placeholders[] = '"address" like ?';
      $parent_addresses[$k] = "$p/%";
    }
    if (count($placeholders) > 0) $placeholders = 'and ('.implode(' or ', $placeholders).') ';
    else $placeholders = '';

    $stm = $this->db->prepare('SELECT * FROM "content" WHERE "active" = 1 '.$placeholders.$orderby.$limit);
    $stm->execute($parent_addresses);
    $content = $this->getObjectsFromQuery($stm);
    return $content;
  }

  public function getOrAddTagsByName(array $tags) {
    $placeholders = array();
    for($i = 0; $i < count($tags); $i++) $placeholders[] = '?';
    $stm = $this->db->prepare('SELECT * FROM "tags" WHERE "tag" IN ('.implode(',',$placeholders).') ORDER BY "tag"');
    $stm->execute($tags);
    $dbTags = $stm->fetchAll(\PDO::FETCH_ASSOC);
    foreach($dbTags as $k => $t) $dbTags[$k] = ContentTag::restoreFromData($t);
    $dbTags = $this->factory->new('dataCollection', null, $dbTags);
    $this->prepareDataCollection($dbTags, 'tags');
    
    foreach($tags as $t) {
      if (!$dbTags->contains('tag', $t)) $dbTags[] = $this->factory->new('contentTag')->set('tag', $t);
    }

    return $dbTags;
  }

  public function getContentTags(Interfaces\Content $content) {
    $collection = $this->factory->new('dataCollection');
    $this->prepareDataCollection($collection, 'tags');

    if ($content[$content::PRIMARY_KEY] === null) return $collection;

    $stm = 'SELECT * FROM "'.$collection->getChildTableName().'" JOIN "'.$collection->getLinkTableName().'" ON ("'.$collection->getChildTableName().'"."'.ContentTag::PRIMARY_KEY.'" = "'.$collection->getLinkTableName().'"."'.$collection->getChildLinkKey().'") WHERE "'.$collection->getLinkTableName().'"."'.$collection->getParentLinkKey().'" = ?';
    ($stm = $this->db->prepare($stm))->execute(array($content[$content::PRIMARY_KEY]));
    foreach($stm->fetchAll(\PDO::FETCH_ASSOC) as $row) $collection[] = ContentTag::restoreFromData($row);
    return $collection;
  }

  public function getParentOf(Interfaces\Content $content) {
    if (strrpos($content->get('address'), '/') === 0) return null;
    $parentAddress = substr($content->get('address'), 0, strrpos($content->get('address'), '/'));
    return $this->getContentByAddress($parentAddress);
  }






  /*****************************
   * Validation Functions
   * **************************/

  /**
   * Validation function to check whether the address of this content is unique across all content
   *
   * This function is called from the `DataClass::validateObject` method, which validates the object
   * against the collection of objects managed by the CMS.
   *
   * @param Content $content
   * @return bool
   */
  public function contentAddressIsUnique(Interfaces\Content $content) {
    $stm = $this->db->prepare('SELECT "id" FROM "content" WHERE "address" = ? and "id" != ?');
    $stm->execute(array($content['address'], $content['id'] ?: 0));
    $rows = $stm->fetchAll(\PDO::FETCH_ASSOC);
    return count($rows) == 0;
  }
  
  /**
   * Validation function to check whether the canonical ID of this object is unique for the its language
   *
   * This function is called from the `DataClass::validateObject` method, which validates the object
   * against the collection of objects managed by the CMS.
   *
   * @param Content $content
   * @return bool
   */
  public function contentCanonicalIdIsUnique(Interfaces\Content $content) {
    $stm = $this->db->prepare('SELECT "id" FROM "content" WHERE "canonicalId" = ? and "lang" = ? and "id" != ?');
    $stm->execute(array($content['canonicalId'], $content['lang'], $content['id'] ?: 0));
    $rows = $stm->fetchAll(\PDO::FETCH_ASSOC);
    return count($rows) == 0;
  }

  /**
   * Validation function to check whether a content tag is unique
   *
   * This function is called from the `DataClass::validateObject` method, which validates the object
   * against the collection of objects managed by the CMS.
   *
   * @param ContentTag $tag
   * @return bool
   */
  public function tagIsUnique(Interfaces\ContentTag $tag) {
    $stm = $this->db->prepare('SELECT 1 FROM "'.$tag::TABLE_NAME.'" WHERE "tag" = ? and "'.$tag::PRIMARY_KEY.'" != ?');
    $stm->execute(array($tag['tag'], $tag[$tag::PRIMARY_KEY]));
    $rows = $stm->fetchAll(\PDO::FETCH_NUM);
    return count($rows) == 0;
  }
    










  /******************************
   * Internal Functions
   * ***************************/


  protected function convertToData(array $values) {
    foreach($values as $k => $v) {
      if (is_string($v) || is_int($v)) continue;
      elseif (is_bool($v)) $values[$k] = (int)$v;
      elseif (is_array($v)) $values[$k] = $this->convertToData($v);
      elseif ($v instanceof \DateTime) $values[$k] = $v->format(\DateTime::ISO8601);
      elseif ($v instanceof \Skel\Interfaces\Uri) $values[$k] = $v->toString();
      elseif ($v instanceof \Skel\Interfaces\Content) $values[$k] = $v->getAddress();
    }
    return $values;
  }



  protected function downgradeDatabase(int $targetVersion, int $fromVersion) {
    // Nothing to downgrade yet
  }



  protected function getObjectsFromQuery(\PDOStatement $stm) {
    $collection = $this->factory->new('dataCollection', 'generic');
    $result = $stm->fetchAll(\PDO::FETCH_ASSOC);
    if (count($result) == 0) return $result;

    foreach($result as $k => $data) $collection[] = $this->dressData($data);
    return $collection;
  }

  protected function dressData(array $data) {
    $obj = $this->factory->create('content', $data['contentClass'], $data);
    $obj->setDb($this);
    return $obj;
  }

  protected function getPrimaryChanges(Interfaces\DataClass $obj) {
    $changes = parent::getPrimaryChanges($obj);
    if ($obj instanceof Interfaces\Content) {
      $changes['setBySystem'] = array();
      foreach($obj as $field => $val) {
        if ($obj->fieldSetBySystem($field)) $changes['setBySystem'][$field] = true;
      }
      $changes['setBySystem'] = json_encode($changes['setBySystem']);
    }
    return $changes;
  }



  protected function prepareDataCollection(Interfaces\DataCollection $c, string $field) {
    if ($field == 'tags') {
      $c->setLinkTableName('contentTags');
      $c->setChildTableName('tags');
      $c->setParentLinkKey('contentId');
      $c->setChildLinkKey('tagId');
    }
  }





  protected function upgradeDatabase(int $targetVersion, int $fromVersion) {
    if ($fromVersion < 1 && $targetVersion >= 1) {
      $this->db->exec('CREATE TABLE "content" ("id" INTEGER PRIMARY KEY, "active" INTEGER NOT NULL DEFAULT 1, "address" TEXT NOT NULL, "author" TEXT NULL, "canonicalId" TEXT NOT NULL, "content" TEXT NOT NULL DEFAULT \'\', "contentClass" TEXT NOT NULL DEFAULT \'content\', "dateCreated" TEXT NOT NULL, "dateExpired" TEXT DEFAULT NULL, "dateUpdated" TEXT NOT NULL, "setBySystem" TEXT NOT NULL DEFAULT \'{}\', "hasImg" INTEGER NOT NULL DEFAULT 0, "imgPrefix" TEXT NULL, "lang" TEXT NOT NULL DEFAULT \'en\', "title" TEXT NOT NULL)');
      $this->db->exec('CREATE TABLE "contentTags" ("id" INTEGER PRIMARY KEY, "contentId" INTEGER NOT NULL, "tagId" INTEGER NOT NULL)');
      $this->db->exec('CREATE TABLE "tags" ("id" INTEGER PRIMARY KEY, "tag" TEXT NOT NULL)');

      $this->db->exec('CREATE INDEX "tags_content_id_index" ON "contentTags" ("contentId","tagId")');
      $this->db->exec('CREATE INDEX "content_main_index" ON "content" ("active","address")');
      $this->db->exec('CREATE INDEX "content_secondary_index" ON "content" ("active","lang","canonicalId")');
      //$this->db->exec('CREATE INDEX "content_dateCreated_index" ON "content" ("dateCreated")');
      //$this->db->exec('CREATE INDEX "content_dateUpdated_index" ON "content" ("dateUpdated")');
    }
  }
}

