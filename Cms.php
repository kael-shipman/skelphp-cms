<?php
/**
 * Cms is a base Cms class that manages database interactions and data interfacing
 *
 * This is a tricky class. The concept is that name of the content classes are stored
 * in the db as part of the object data. Therefore, the actual Cms object that you use must
 * know how to handle all of the classes of content that your app uses. This is accomplished
 * (theoretically) by overriding six methods in all descendent classes. Importantly, any
 * of these six methods throws an `UnknownContentClassException` when it encounters content
 * classes it doesn't explicitly know how to handle. Thus, when you override these methods,
 * you can first call `parent` to save the basic content data, then test for all the classes
 * you've added with your Cms derivative that need special attention. If an
 * `UnknownContentClassException` is thrown, that means that the given class couldn't be handled
 * and should be handled explicitly by your version of the method.
 *
 * The six methods are:
 *
 * * `validateContent` - Does what it says
 * * `__saveContent` - Performs the actual mechanics of saving after validation
 * * `saveExtraField` - Used to handle fields that may not be in the "content" table
 * * `deleteContent` - Can be overridden to delete additional records or resources related to the content
 * * `addAuxData` - called from all of the `getContent*` methods and used to augment the returned array with the correct fields
 * * `dressData` - used to create Data Objects based on content class names in data array
 */
namespace Skel;

class Cms extends Db implements Interfaces\Cms {
  const VERSION = 1;
  const SCHEMA_NAME = "SkelCms";
  protected static $__validContentClasses = null;
  protected static $validContentClasses = array('content', 'page', 'post');
  protected $this->errors;


  public function deleteContent(Interfaces\Content $content) {
    if (!$content->getId()) return true;

    $uri = $content->getUri();
    if ($uri->getScheme() == 'file') {
      $filepath = $this->getContentDir()->getPath().'/'.$uri()->getHost().'/'.$uri->getPath();
      @unlink($filepath);
    }

    $this->db->beginTransaction();
    $this->db->exec('DELETE FROM "content_tags" WHERE "contentId" = '.$content->getId());
    $this->db->exec('DELETE FROM "content" WHERE "id" = '.$content->getId());
    $this->db->commit();
  }



  public function getContentAtUri(Interfaces\Uri $dataUri) {
    if ($dataUri->getScheme() != 'file') throw new IllegalContentUriException("Sorry, don't know how to get content from anywhere but the local machine :(");
    if ($dataUri->getHost() != 'pages') throw new IllegalContentUriException("Sorry, don't know how to get content from hosts other than 'pages', which references the currently configured content directory");

    //TODO: Figure out better way to get a configurable content directory and how to deal with languages
    $dir = $this->getContentDir()->getPath();

    if ($dataUri->getHost() == 'pages') {
      $path = "$dir/pages".$dataUri->getPath();
      if (is_file($path)) return file_get_contents($path);
    }

    return '';
  }

  public function getContentByAddress(string $addr) {
    $stm = $this->db->prepare('SELECT * FROM "content" WHERE "active" = 1 and "address" = ?');
    $stm->execute(array($addr));
    $content = $this->getContentFromStatement($stm);
    if ($content) $content = $content[0];
    return $content;
  }

  public function getContentById(int $id) {
    $stm = $this->db->prepare('SELECT * FROM "content" WHERE "id" = ?');
    $stm->execute(array($id));
    $content = $this->getContentFromStatement($stm);
    if ($content) $content = $content[0];
    return $content;
  }

  public function getContentWhere(string $where, array $values=array()) {
    $stm = $this->db->prepare('SELECT * FROM "content" WHERE "active" = 1 and '.$where);
    $stm->execute($values);
    $rows = $this->getContentFromStatement($stm);
    return $rows;
  }

  public function getContentIndex(string $category=null, int $limit=null, int $page=1) {
    $orderby = 'ORDER BY "dateCreated" DESC ';
    if ($limit) $limit = "LIMIT $limit OFFSET ".(($page-1)*$limit);
    if (!$category) {
      $stm = $this->db->prepare('SELECT * FROM "content" WHERE "active" = 1 and "category" IS NOT NULL '.$orderby.$limit);
      $stm->execute();
    } else {
      $stm = $this->db->prepare('SELECT * FROM "content" WHERE "active" = 1 and "category" = ? '.$orderby.$limit);
      $stm->execute(array($category));
    }
    $content = $this->getContentFromStatement($stm);
    return $content;
  }



  public static function getValidContentClasses() {
    if (static::$__validContentClasses) return static::$__validContentClasses;

    $classes = static::$validContentClasses;
    $parent = static::class;
    while ($parent = get_parent_class($parent)) $classes = array_merge($classes, $parent::$validContentClasses ?: array());
    static::$__validContentClasses = $classes;

    return static::$__validContentClasses;
  }

  public static function setValidContentClasses(array $classes) {
    static::$validContentClasses = $classes;
  }



  public function saveContent(Interfaces\Content $content) {
    if (($errcount = count($content->getErrors())) > 0) throw new InvalidDataException("You have $errcount errors to fix: ".implode("; ", $content->getErrors()).";");
    if (!$this->validateContent($content)) throw new InvalidContentException("There are errors in your content: ".implode('; ', $this->getErrors());

    return $this->__saveContent($content);
  }

  protected function __saveContent(Interfaces\Content $content) {
    $changes = $content->getChanges();

    // Save the base fields that all content shares
    $deferrred = array();
    if (array_key_exists('content', $changes)) {
      $deferred['content'] = $changes['content'];
      unset($changes['content']);
    }
    if (array_key_exists('tags', $changes)) {
      $defferred['tags'] = $changes['tags'];
      unset($changes['tags']);
    }

    $placeholders = array();
    for($i = 0; $i < count($changes); $i++) $placeholders[] = '?';

    if ($id = $content->getId()) {
      $stm = $this->db->prepare('UPDATE "content" SET "'.implode('" = ?, "', array_keys($changes)).'" = ? WHERE "id" = ?');
      $stm->execute(array_merge($changes, array($id)));
    } else {
      $stm = $this->db->prepare('INSERT INTO "content" ("'.implode('", "', array_keys($changes)).'") VALUES ('.implode(',',$placeholders).')');
      $stm->execute(array_values($changes));
      $id = $this->db->lastInsertId();
      $content->setId($id);
    }

    foreach($deferred as $field => $val) $this->saveExtraField($content, $field, $val);

    return true;
  }

  protected function saveExtraField(Interfaces\Content $content, string $field, $val) {
    $id = $content->getId();

    // Save Content Field
    if ($field == 'content') {
      if ($content->getContentUri()->getScheme() != 'file') throw new IllegalDataUriException("Sorry, don't know how to handle schemes other than `file` :(");
      if ($content->getContentUri()->getHost() != 'pages') throw new IllegalDataUriException("Sorry, don't know how to handle hosts other than 'pages', which references the `pages` directory of the currently configured content directory");

      $path = $this->getContentDir()->getPath()."/pages";
      if (!is_dir($path)) throw new NonexistentFileException("Can't find the `pages` directory within the current content directory. Searched at `$path`");

      $path .= $content->getContentUri()->getPath();
      $filename = basename($path);
      $dir = substr($path, 0, strlen($path)-strlen($filename)-1);

      if (!is_dir($dir)) @mkdir($dir, 0777, true); 
      file_put_contents($path, $val);
      return true;

    // Save content Tags
    } elseif ($field == 'tags') {
      $currentTags = array();
      $select = 'SELECT "tag" FROM "content_tags" WHERE "contentId" = '.$id;
      foreach($this->db->query($select, \PDO::FETCH_ASSOC) as $row) $currentTags[] = $row['tag'];

      foreach($currentTags as $v) {
        if (array_search($v, $val) === false) {
          $stm = $this->db->prepare('DELETE FROM "content_tags" WHERE "contentId" = ? and "tag" = ?');
          $stm->execute(array($id, $v));
        }
      }

      foreach($val as $v) {
        if (array_search($v, $currentTags) === false) {
          $stm = $this->db->prepare('INSERT INTO "content_tags" ("tag", "contentId") VALUES (?, ?)');
          $stm->execute(array($v, $id));
        }
      }
      return true;

    // If the field is unknown, throw exception
    } else {
      throw new UnknownContentFieldException("Don't know how to handle field `$field`");
    }
  }


  public function validateContent(Interfaces\Content $content) {
    $valid = true;

    // General validations first: No content can have an invalid class or a duplicate address

    // Validate ContentClass
    if (array_search($content->getContentClass(), static::getValidContentClasses()) === false) {
      $this->setError('contentClass', "The specified content class `".$content->getContentClass()."` is not one of the currently designated valid content classes (`".implode('`, `', static::getValidContentClasses())."`).");
      $valid = false;
    }

    // Validate Address
    $stm = $this->db->prepare('SELECT "id" FROM "content" WHERE "address" = ? and "id" != ?');
    $stm->execute(array($content->getAddress(), $content->getId() ?: 0));
    $rows = $stm->fetchAll(\PDO::FETCH_ASSOC);
    if (count($rows) > 0) {
      $this->setError('address', 'The address you specified for this content ('.$a.') is already being used by other content.');
      $valid = false;
    }

    return $valid;
  }









  /******************************
   * Internal Functions
   * ***************************/

  protected function attachTagsToData(array $data) {
    $ids = array();
    $placeholders = array();
    foreach($data as $k => $r) {
      $ids[] = $r['id'];
      $placeholders[] = '?';
    }

    $stm = $this->db->prepare('SELECT "contentId", "tag" FROM "content_tags" WHERE "contentId" in ('.implode(',', $placeholders).')');
    $stm->execute($ids);
    $result = $stm->fetchAll(\PDO::FETCH_ASSOC);
    $tags = array();
    foreach($result as $r) {
      if (!array_key_exists($r['contentId'], $tags)) $tags[$r['contentId']] = array();
      $tags[$r['contentId']][] = $r['tag'];
    }

    foreach($data as $k => $r) {
      if (array_key_exists($r['id'], $tags)) $r['tags'] = $tags[$r['id']];
      else $r['tags'] = array();
      $data[$k] = $r;
    }
    
    return $data;
  }




  protected function dressData(array $data) {
    switch($data['contentClass']) {
      case 'content' : return new \Skel\Content($data); break;
      case 'page' : return new \Skel\Page($data);
      case 'post' : return new \Skel\Post($data);
      default : throw new \Skel\UnknownContentClassException("Don't know how to dress `$data[contentClass]` content.");
    }
  }

  protected function downgradeDatabase(int $targetVersion, int $fromVersion) {
    // Nothing to downgrade yet
  }


  protected function getContentFromStatement(\PDOStatement $stm) {
    $result = $stm->fetchAll(\PDO::FETCH_ASSOC);
    if (count($result) == 0) return null;

    foreach($result as $k => $data) $result[$k] = $this->dressData($this->addAuxData($data));
    return $result;
  }




  protected function addAuxData(array $data) {
    $data['content'] = $this->getContentAtUri(new Uri($r['contentUri']));
    $data = $this->attachTagsToData($data);
    return $data;
  }




  protected function saveContentForContent(int $id, string $content) {
    $contentUri = $this->db->query('SELECT "contentUri" FROM "content" WHERE "id" = '.$id);
    $contentUri = $contentUri->fetch(\PDO::FETCH_ASSOC);
    $contentUri = new Uri($contentUri['contentUri']);

    return $this;
  }

  protected function saveTagsForContent(int $id, array $newTags) {
    $currentTags = array();
    foreach($this->db->query('SELECT "tag" FROM "content_tags" WHERE "contentId" = '.$id, \PDO::FETCH_ASSOC) as $row) {
      $currentTags[] = $row['tag'];
    }

    foreach($currentTags as $v) {
      if (array_search($v, $newTags) === false) {
        $stm = $this->db->prepare('DELETE FROM "content_tags" WHERE "contentId" = ? and "tag" = ?');
        $stm->execute(array($id, $v));
      }
    }

    foreach($newTags as $v) {
      if (array_search($v, $currentTags) === false) {
        $stm = $this->db->prepare('INSERT INTO "content_tags" ("tag", "contentId") VALUES (?, ?)');
        $stm->execute(array($v, $id));
      }
    }
  }



  protected function upgradeDatabase(int $targetVersion, int $fromVersion) {
    if ($fromVersion < 1 && $targetVersion >= 1) {
      $this->db->exec('CREATE TABLE "content" ("id" INTEGER PRIMARY KEY NOT NULL, "active" INTEGER NOT NULL DEFAULT 1, "address" TEXT NOT NULL, "author" TEXT NULL, "canonicalId" TEXT NOT NULL, "category" TEXT NULL, "contentClass" TEXT NOT NULL DEFAULT \'content\', "contentType" TEXT NOT NULL DEFAULT \'text/plain; charset=UTF-8\', "contentUri" TEXT NOT NULL, "dateCreated" TEXT NOT NULL, "dateExpired" TEXT DEFAULT NULL, "dateUpdated" TEXT NOT NULL, "hasImg" INTEGER NOT NULL DEFAULT 0, "imgPrefix" TEXT NULL, "lang" TEXT NOT NULL DEFAULT \'en\', "title" TEXT NOT NULL)');
      $this->db->exec('CREATE TABLE "content_tags" ("id" INTEGER PRIMARY KEY NOT NULL, "contentId" INTEGER NOT NULL, "tag" TEXT NOT NULL)');

      $this->db->exec('CREATE INDEX "tags_content_id_index" ON "content_tags" ("contentId")');
      $this->db->exec('CREATE INDEX "tags_index" ON "content_tags" ("tag")');
      $this->db->exec('CREATE INDEX "content_active_index" ON "content" ("active")');
      $this->db->exec('CREATE INDEX "content_category_index" ON "content" ("category")');
      $this->db->exec('CREATE INDEX "content_dateCreated_index" ON "content" ("dateCreated")');
      $this->db->exec('CREATE INDEX "content_dateUpdated_index" ON "content" ("dateUpdated")');
      $this->db->exec('CREATE INDEX "content_lang_index" ON "content" ("lang")');
    }
  }



  /********************
   * Errors
   * *****************/

  protected function setError(string $field, string $msg) { $this->errors[$field] = $msg; }
  public function getErrors() { return $this->errors; }
  protected function clearErrors() { $this->errors = array(); }
}

