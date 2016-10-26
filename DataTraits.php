<?php
namespace Skel;

trait CmsDbSqlite {
  protected static $validContentClasses = array('Content', 'Post');
  protected $contentDir;

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
    //TODO: Figure out better way to do this. This is janky as hell

    $stm = $this->prepare('SELECT "content".* FROM "content" JOIN "content_addresses" ON ("content"."id" = "contentId") WHERE "address" = ?');
    $stm->execute(array($addr));
    $content = $this->getContentData($stm);
    if ($content) $content = $content[0];
    return $content;
  }

  public function getContentDataWhere(string $where, array $values=array()) {
    $stm = $this->prepare('SELECT * FROM "content" WHERE '.$where);
    $stm->execute($values);
    $rows = $stm->fetchAll(\PDO::FETCH_ASSOC);
    $rows = $this->prepareContentData($rows);
    return $rows;
  }




  public function saveContentData(int $id=null, $data) {
    if (isset($data['content'])) {
      $content = $data['content'];
      unset($data['content']);
    }
    if (isset($data['addresses'])) {
      $addrs = $data['addresses'];
      unset($data['addresses']);
    }
    if (isset($data['tags'])) {
      $tags = $data['tags'];
      unset($data['tags']);
    }
    if (isset($data['attributes'])) {
      $attrs = $data['attributes'];
      unset($data['attributes']);
    }
    if (isset($data['id'])) unset($data['id']);

    $placeholders = array();
    for($i = 0; $i < count($data); $i++) $placeholders[] = '?';
    if (!$id) {
      $stm = $this->prepare('INSERT INTO "content" ("'.implode('", "', array_keys($data)).'") VALUES ('.implode(',',$placeholders).')');
      $stm->execute(array_values($data));
      $id = $this->lastInsertId();
    } else {
      $stm = $this->prepare('UPDATE "content" SET "'.implode('" = ?, "', array_keys($data)).'" = ? WHERE "id" = ?');
      $stm->execute(array_merge(array_values($data), array($id)));
    }

    if ($content) $this->saveContentForContent($id, $content);
    if ($addrs) $this->saveAddrsForContent($id, $addrs);
    if ($tags) $this->saveTagsForContent($id, $tags);
    if ($attrs) $this->saveAttrsForContent($id, $attrs);

    return $id;
  }

  public static function setValidContentClasses(array $classes) {
    $valid = array();
    foreach($classes as $k => $c) {
      if (is_numeric($k)) $valid[$c] = $c;
      else $valid[$k] = $c;
    }
    static::$validContentClasses = $valid;
  }














  /******************************
   * Internal Functions
   * ***************************/


  protected function attachAddressesToContent(array $data) {
    $ids = array();
    $placeholders = array();
    foreach($data as $k => $r) {
      $ids[] = $r['id'];
      $placeholders[] = '?';
    }

    $stm = $this->prepare('SELECT "contentId", "address" FROM "content_addresses" WHERE "active" = 1 and "contentId" in ('.implode(',', $placeholders).')');
    $stm->execute($ids);
    $result = $stm->fetchAll(\PDO::FETCH_ASSOC);

    $addrs = array();
    foreach($result as $r) {
      if (!isset($addrs[$r['contentId']])) $addrs[$r['contentId']] = array();
      $addrs[$r['contentId']][] = $r['address'];
    }

    foreach($data as $k => $r) {
      if (isset($addrs[$r['id']])) $r['addresses'] = $addrs[$r['id']];
      else $r['addresses'] = array();
      $data[$k] = $r;
    }

    return $data;
  }

  protected function attachAttributesToContent(array $data) {
    $ids = array();
    $placeholders = array();
    foreach($data as $k => $r) {
      $ids[] = $r['id'];
      $placeholders[] = '?';
    }

    $stm = $this->prepare('SELECT "contentId", "key", "value" FROM "content_attributes" WHERE "contentId" in ('.implode(',', $placeholders).')');
    $stm->execute($ids);
    $result = $stm->fetchAll(\PDO::FETCH_ASSOC);
    $attrs = array();
    foreach($result as $r) {
      if (!isset($attrs[$r['contentId']])) $attrs[$r['contentId']] = array();
      $attrs[$r['contentId']][$r['key']] = $r['value'];
    }

    foreach($data as $k => $r) {
      if (isset($attrs[$r['id']])) $r['attributes'] = $attrs[$r['id']];
      else $r['attributes'] = array();
      $data[$k] = $r;
    }

    return $data;
  }

  protected function attachContentToContent(array $data) {
    foreach($data as $k => $r) {
      $r['content'] = $this->getContentAtUri(new Uri($r['contentUri']));
      $data[$k] = $r;
    }
    return $data;
  }

  protected function attachTagsToContent(array $data) {
    $ids = array();
    $placeholders = array();
    foreach($data as $k => $r) {
      $ids[] = $r['id'];
      $placeholders[] = '?';
    }

    $stm = $this->prepare('SELECT "contentId", "tag" FROM "content_tags" WHERE "contentId" in ('.implode(',', $placeholders).')');
    $stm->execute($ids);
    $result = $stm->fetchAll(\PDO::FETCH_ASSOC);
    $tags = array();
    foreach($result as $r) {
      if (!isset($tags[$r['contentId']])) $tags[$r['contentId']] = array();
      $tags[$r['contentId']][] = $r['tag'];
    }

    foreach($data as $k => $r) {
      if (isset($tags[$r['id']])) $r['tags'] = $tags[$r['id']];
      else $r['tags'] = array();
      $data[$k] = $r;
    }
    
    return $data;
  }



  protected function downgradeCmsDatabase(int $targetVersion, int $fromVersion) {
  }



  protected function getContentData(\PDOStatement $stm) {
    if ($stm->rowCount() == 0) return null;

    $result = $this->prepareContentData($stm->fetchAll(\PDO::FETCH_ASSOC));

    foreach($result as $k => $r) {
      if (!($class = static::$validContentClasses[$r['contentClass']])) throw new InvalidDataException("Don't know how to handle objects of type `$r[contentClass]`");
      elseif (!class_exists($class)) throw new InvalidDataException("Content class `$r[contentClass] is not defined in the current environment.");
      else $result[$k] = new $class($r);
    }
  }




  protected function prepareContentData(array $data) {
    $data = $this->attachAddressesToContent($data);
    $data = $this->attachAttributesToContent($data);
    $data = $this->attachContentToContent($data);
    $data = $this->attachTagsToContent($data);

    foreach($data as $k => $r) {
      if ($r['contentClass'] == 'post') {
        $r['author'] = $r['attributes']['author'];
        $r['imgPrefix'] = $r['attributes']['imgPrefix'];
        $r['category'] = $r['attributes']['category'];
      }
      $data[$k] = $r;
    }
    return $data;
  }




  protected function saveAddrsForContent(int $id, array $newAddrs) {
    $currentAddrs = array();
    foreach($this->query('SELECT "address" FROM "content_addresses" WHERE "contentId" = '.$id, \PDO::FETCH_ASSOC) as $row) {
      $currentAddrs[] = $row['address'];
    }

    foreach($currentAddrs as $addr) {
      if (array_search($addr, $newAddrs) === false) {
        $stm = $this->prepare('DELETE FROM "content_addresses" WHERE "contentId" = ? and "address" = ?');
        $stm->execute(array($id, $addr));
      }
    }

    foreach($newAddrs as $addr) {
      if (array_search($addr, $currentAddrs) === false) {
        $stm = $this->prepare('INSERT INTO "content_addresses" ("address", "contentId") VALUES (?, ?)');
        $stm->execute(array($addr, $id));
      }
    }

    return $this;
  }

  protected function saveAttrsForContent(int $id, $newAttrs) {
    $currentAttrs = array();
    foreach($this->query('SELECT "key", "value" FROM "content_attributes" WHERE "contentId" = '.$id, \PDO::FETCH_ASSOC) as $row) {
      $currentAttrs[$row['key']] = $row['value'];
    }

    foreach($currentAttrs as $k => $v) {
      if (!isset($newAttrs[$k])) {
        $stm = $this->prepare('DELETE FROM "content_attributes" WHERE "contentId" = ? and "key" = ?');
        $stm->execute(array($id, $k));
      }
    }

    foreach($newAttrs as $k => $v) {
      if (!isset($currentAttrs[$k])) {
        $stm = $this->prepare('INSERT INTO "content_attributes" ("key", "value", "contentId") VALUES (?, ?, ?)');
        $stm->execute(array($k, $v, $id));
      } elseif ($currentAttrs[$k] != $v) {
        $stm = $this->prepare('UPDATE "content_attributes" SET "value" = ? WHERE "key" = ? and "contentId" = ?');
        $stm->execute(array($v, $k, $id));
      }
    }

    return $this;
  }

  protected function saveContentForContent(int $id, string $content) {
    $contentUri = $this->query('SELECT "contentUri" FROM "content" WHERE "id" = '.$id);
    $contentUri = $contentUri->fetch(\PDO::FETCH_ASSOC);
    $contentUri = new Uri($contentUri['contentUri']);

    if ($contentUri->getScheme() != 'file') throw new IllegalContentUriException("Sorry, don't know how to handle schemes other than `file` :(");
    if ($contentUri->getHost() != 'pages') throw new IllegalContentUriException("Sorry, don't know how to handle hosts other than 'pages', which references the `pages` directory of the currently configured content directory");

    $path = $this->getContentDir()->getPath()."/pages";
    if (!is_dir($path)) throw new NonexistentFileException("Can't find the `pages` directory within the current content directory. Searched at `$path`");

    $path .= $contentUri->getPath();
    $filename = basename($path);
    $dir = substr($path, 0, strlen($path)-strlen($filename)-1);

    if (!is_dir($dir)) @mkdir($dir, 0777, true); 

    file_put_contents($path, $content);

    return $this;
  }

  protected function saveTagsForContent(int $id, array $newTags) {
    $currentTags = array();
    foreach($this->query('SELECT "tag" FROM "content_tags" WHERE "contentId" = '.$id, \PDO::FETCH_ASSOC) as $row) {
      $currentTags[] = $row['tag'];
    }

    foreach($currentTags as $v) {
      if (array_search($v, $newTags) === false) {
        $stm = $this->prepare('DELETE FROM "content_tags" WHERE "contentId" = ? and "tag" = ?');
        $stm->execute(array($id, $v));
      }
    }

    foreach($newTags as $v) {
      if (array_search($v, $currentTags) === false) {
        $stm = $this->prepare('INSERT INTO "content_tags" ("tag", "contentId") VALUES (?, ?)');
        $stm->execute(array($v, $id));
      }
    }
  }




  protected function upgradeCmsDatabase(int $targetVersion, int $fromVersion) {
    if ($fromVersion < 1 && $targetVersion >= 1) {
      $this->exec('CREATE TABLE "content" ("id" INTEGER PRIMARY KEY NOT NULL, "active" INTEGER NOT NULL DEFAULT 1, "canonicalAddr" TEXT NOT NULL, "contentClass" TEXT NOT NULL DEFAULT \'content\', "contentType" TEXT NOT NULL DEFAULT \'text/plain; charset=UTF-8\', "contentUri" TEXT NOT NULL, "dateCreated" TEXT NOT NULL, "dateExpired" TEXT NOT NULL, "dateUpdated" TEXT NOT NULL, "lang" TEXT NOT NULL DEFAULT \'en\', "title" TEXT NOT NULL)');
      $this->exec('CREATE TABLE "content_addresses" ("id" INTEGER PRIMARY KEY NOT NULL, "active" INTEGER NOT NULL DEFAULT 1, "address" TEXT NOT NULL, "contentId" INTEGER NOT NULL)');
      $this->exec('CREATE TABLE "content_attributes" ("id" INTEGER PRIMARY KEY NOT NULL, "contentId" INTEGER NOT NULL, "key" TEXT NOT NULL, "value" TEXT NOT NULL)');
      $this->exec('CREATE TABLE "content_tags" ("id" INTEGER PRIMARY KEY NOT NULL, "contentId" INTEGER NOT NULL, "tag" TEXT NOT NULL)');

      $this->exec('CREATE INDEX "tags_content_id_index" ON "content_tags" ("contentId")');
      $this->exec('CREATE INDEX "tags_index" ON "content_tags" ("tag")');
      $this->exec('CREATE INDEX "attrs_contentId_index" ON "content_attributes" ("contentId")');
      $this->exec('CREATE INDEX "attrs_key_index" ON "content_attributes" ("key")');
      $this->exec('CREATE INDEX "attrs_value_index" ON "content_attributes" ("value")');
      $this->exec('CREATE INDEX "addrs_address_index" ON "content_addresses" ("address")');
      $this->exec('CREATE INDEX "addrs_contentId_index" ON "content_addresses" ("contentId")');
      $this->exec('CREATE INDEX "content_dateCreated_index" ON "content" ("dateCreated")');
      $this->exec('CREATE INDEX "content_dateUpdated_index" ON "content" ("dateUpdated")');
      $this->exec('CREATE INDEX "content_lang_index" ON "content" ("lang")');
    }
  }
}
