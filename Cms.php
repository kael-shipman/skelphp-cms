<?php
/**
 * Cms is a base Cms class that manages database interactions and data interfacing
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

class Cms extends Db implements Interfaces\Cms, Interfaces\ErrorHandler {
  use ErrorHandlerTrait;

  const VERSION = 1;
  const SCHEMA_NAME = "SkelCms";









  /****************************
   * Public Content Methods
   * *************************/

  public function deleteContent(Interfaces\Content $content) {
    if (!$content['id']) return true;

    $this->db->beginTransaction();
    $this->db->exec('DELETE FROM "content_tags" WHERE "contentId" = '.$content['id']);
    $this->db->exec('DELETE FROM "content" WHERE "id" = '.$content['id']);
    $this->db->commit();
  }




  public function getContentByAddress($val) {
    if (is_array($val)) {
      $single = false;
      $placeholders = array();
      for($i=0; $i < count($val); $i++) $placeholders = '?';
      $query = ' in ('.implode(',', $placeholders).')';
    } else {
      $single = true;
      $query = ' = ?';
      $val = array($val);
    }
    $stm = $this->db->prepare('SELECT * FROM "content" WHERE "active" = 1 and "address" '.$query);
    $stm->execute($val);
    $content = $this->getObjectsFromQuery($stm);
    if (count($content) > 0) {
      if ($single) return $content[0];
      else return $content;
    } else {
      if ($single) return null;
      else return $content;
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

  public function getContentIndex(array $parents=null, int $limit=null, int $page=1, $orderby='"dateCreated" DESC') {
    $orderby = 'ORDER BY '.$orderby.' ';
    if ($limit) $limit = "LIMIT $limit OFFSET ".(($page-1)*$limit);

    if (!$parents) $parents = array();
    $placeholders = array();
    foreach($parents as $k => $p) {
      $placeholders[] = '"address" like ?';
      $parents[$k] = "$p/%";
    }
    if (count($placeholders) > 0) $placeholders = 'and ('.implode(' or ', $placeholders).') ';
    else $placeholders = '';

    $stm = $this->db->prepare('SELECT * FROM "content" WHERE "active" = 1 '.$placeholders.$orderby.$limit);
    $stm->execute($parents);
    $content = $this->getObjectsFromQuery($stm);
    return $content;
  }

  public function getParentOf(Interfaces\Content $content) {
    if (strrpos($content->get('address'), '/') === 0) return null;
    $parentAddress = substr($content->get('address'), 0, strrpos($content->get('address'), '/'));
    return $this->getContentByAddress($parentAddress);
  }




  // TODO: Rename and refactor this. Not everything is "Content" (e.g., tags, media, etc.)
  public function saveContent(Interfaces\Content $content) {
    if (($errcount = $content->numErrors()) > 0) throw new InvalidDataException("You have $errcount errors to fix: ".implode("; ", $content->getErrors()).";");
    if (!$this->validateContent($content)) throw new InvalidContentException("There are errors in your content: ".implode('; ', $this->getErrors()).";");

    $contentChanges = array();
    $extraChanges = array();
    foreach($content->getDefinedFields() as $field) {
      $value = $content->getRaw($field);
      if ($value instanceof DataCollection) $extraChanges[] = $value;
      elseif ($content->fieldHasChanged($field)) $contentChanges[$field] = $value;
    }

    if ($id = $content['id']) {
      $stm = $this->db->prepare('UPDATE "content" SET "'.implode('" = ?, "', array_keys($contentChanges)).'" = ? WHERE "id" = ?');
      $stm->execute(array_merge($contentChanges, array($id)));
    } else {
      $placeholders = array();
      for($i = 0; $i < count($contentChanges); $i++) $placeholders[] = '?';

      $stm = $this->db->prepare('INSERT INTO "content" ("'.implode('", "', array_keys($contentChanges)).'") VALUES ('.implode(',',$placeholders).')');
      $stm->execute(array_values($contentChanges));
      $id = $this->db->lastInsertId();
      $content['id'] = $id;
    }

    //foreach($extraChanges as $field => $value) $this->saveExtraField($content, $field, $value);
  }

  public function validateContent(Interfaces\Content $content) {
    // General validations first: No content can have a duplicate address

    // Validate Address
    $stm = $this->db->prepare('SELECT "id" FROM "content" WHERE "address" = ? and "id" != ?');
    $stm->execute(array($content['address'], $content['id'] ?: 0));
    $rows = $stm->fetchAll(\PDO::FETCH_ASSOC);
    if (count($rows) > 0) {
      $this->setError('address', 'The address you specified for this content ('.$content['address'].') is already being used by other content.', 'uniqueness');
    } else {
      $this->clearError('address', 'uniqueness');
    }

    // Validate uniqueness of canonicalId + Lang
    $stm = $this->db->prepare('SELECT "id" FROM "content" WHERE "canonicalId" = ? and "lang" = ? and "id" != ?');
    $stm->execute(array($content['canonicalId'], $content['lang'], $content['id'] ?: 0));
    $rows = $stm->fetchAll(\PDO::FETCH_ASSOC);
    if (count($rows) > 0) {
      $this->setError('canonicalId', 'The canonical Id you specified for this content ('.$content['canonicalId'].') is already being used by other content with the same language ('.$content['lang'].'). You must either change the language or change the canonical Id.', 'uniqueness');
    } else {
      $this->clearError('canonicalId', 'uniqueness');
    }

    return $this->numErrors() == 0;
  }








  /******************************
   * Internal Functions
   * ***************************/

  /**
   * This is done in a strange manner to optimize efficiency. Rather than shooting off separate queries
   * for each result row, it shoots off one query to get all tags for all pertinent results, then organizes
   * the tags into a structure that it uses to populate each result's tags array
   */
  protected function attachContentAttributes(string $field, string $table, string $column, array &$data) {
    if (!is_numeric(current(array_keys($data)))) {
      $data = array($data);
      $single = true;
    }

    $ids = array();
    $placeholders = array();
    foreach($data as $k => $r) {
      $ids[] = $r['id'];
      $placeholders[] = '?';
    }

    $stm = $this->db->prepare('SELECT * FROM "'.$table.'" WHERE "contentId" in ('.implode(', ', $placeholders).')');
    $stm->execute($ids);
    $result = $stm->fetchAll(\PDO::FETCH_ASSOC);
    $fields = array();
    foreach($result as $r) {
      if (!array_key_exists($r['contentId'], $fields)) $fields[$r['contentId']] = array();
      $fields[$r['contentId']][] = $r;
    }





    /*****************************
     * Need to decide how we'll handle tags and other things like that.
     * We need to manage them as a collection of data rows owned by an object. When Cms::saveContent finds a
     * content attribute that's an instance of DataCollection, it must be able to process the contents in
     * a way that's sane in the context of the owner object. (For example, we want to add and remove content/tag
     * pairs from the content_tag table according to what's in the Collection -- not just update the rows
     * of the collection in the DB. However, in other instances, we may indeed want to update the rows. I
     * believe this has something to do with many-to-one vs many-to-many relationships.)
     *****************************/








    foreach($data as $k => $r) {
      if (array_key_exists($r['id'], $fields)) $r[$field] = new DataCollection($fields[$r['id']]);
      else $r[$field] = new DataCollection();
      $data[$k] = $r;
    }

    return $single ? $data[0] : $data;
  }



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

  protected function dressData(array $data) {
    switch($data['contentClass']) {
    case 'page' :
      return \Skel\Page::restoreFromData($data);
    case 'post' :
      return \Skel\Post::restoreFromData($data);
    default : throw new \Skel\UnknownContentClassException("Don't know how to dress `$data[contentClass]` content.");
    }
  }

  protected function downgradeDatabase(int $targetVersion, int $fromVersion) {
    // Nothing to downgrade yet
  }


  protected function getObjectsFromQuery(\PDOStatement $stm) {
    $result = $stm->fetchAll(\PDO::FETCH_ASSOC);
    if (count($result) == 0) return $result;

    $this->addAuxData($result);
    foreach($result as $k => $data) $result[$k] = $this->dressData($data);
    return $result;
  }




  protected function addAuxData(array &$data) {
    if (!is_numeric(current(array_keys($data)))) {
      $data = array($data);
      $single = true;
    }

    $this->attachContentAttributes('tags', 'content_tags', 'tag', $data);

    if ($single) return $data[0];
    else return $data;
  }



  protected function saveExtraField(Interfaces\Content $content, string $field, $val) {
    $id = $content['id'];

    // Save content Tags
    if ($field == 'tags') {
      $this->updateContentAttributesTable('content_tags', 'tag', $content['id'], $val);
      return true;

    // If the field is unknown, throw exception
    } else {
      throw new UnknownContentFieldException("Don't know how to handle field `$field`");
    }
  }

  protected function updateContentAttributesTable(string $table, string $field, int $contentId, array $val) {
    $current = array();
    $currentSelect = 'SELECT "'.$field.'" FROM "'.$table.'" WHERE "contentId" = '.$contentId;
    ($stm = $this->db->prepare($currentSelect))->execute();
    foreach($stm->fetchAll(\PDO::FETCH_NUM) as $row) $current[] = $row[0];

    foreach($current as $v) {
      if (array_search($v, $val) === false) {
        $stm = $this->db->prepare('DELETE FROM "'.$table.'" WHERE "contentId" = ? and "'.$field.'" = ?');
        $stm->execute(array($contentId, $v));
      }
    }

    foreach($val as $v) {
      if (array_search($v, $current) === false) {
        $stm = $this->db->prepare('INSERT INTO "'.$table.'" ("'.$field.'", "contentId") VALUES (?, ?)');
        $stm->execute(array($v, $contentId));
      }
    }
    return true;
  }




  protected function upgradeDatabase(int $targetVersion, int $fromVersion) {
    if ($fromVersion < 1 && $targetVersion >= 1) {
      $this->db->exec('CREATE TABLE "content" ("id" INTEGER PRIMARY KEY, "active" INTEGER NOT NULL DEFAULT 1, "address" TEXT NOT NULL, "author" TEXT NULL, "canonicalId" TEXT NOT NULL, "content" TEXT NOT NULL DEFAULT \'\', "contentClass" TEXT NOT NULL DEFAULT \'content\', "dateCreated" TEXT NOT NULL, "dateExpired" TEXT DEFAULT NULL, "dateUpdated" TEXT NOT NULL, "setBySystem" TEXT NOT NULL DEFAULT \'{}\', "hasImg" INTEGER NOT NULL DEFAULT 0, "imgPrefix" TEXT NULL, "lang" TEXT NOT NULL DEFAULT \'en\', "title" TEXT NOT NULL)');
      $this->db->exec('CREATE TABLE "content_tags" ("id" INTEGER PRIMARY KEY, "contentId" INTEGER NOT NULL, "tag" TEXT NOT NULL)');

      $this->db->exec('CREATE INDEX "tags_content_id_index" ON "content_tags" ("contentId","tag")');
      $this->db->exec('CREATE INDEX "tags_index" ON "content_tags" ("tag", "contentId")');
      $this->db->exec('CREATE INDEX "content_main_index" ON "content" ("active","address")');
      $this->db->exec('CREATE INDEX "content_secondary_index" ON "content" ("active","lang","canonicalId")');
      $this->db->exec('CREATE INDEX "content_dateCreated_index" ON "content" ("dateCreated")');
      $this->db->exec('CREATE INDEX "content_dateUpdated_index" ON "content" ("dateUpdated")');
    }
  }
}

