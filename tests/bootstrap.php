<?php

$path = '.';

require_once $path.'/vendor/autoload.php';
require_once $path.'/vendor/skel/interfaces/Exceptions.php';
require_once $path.'/vendor/skel/interfaces/Interfaces.php';
require_once $path.'/vendor/skel/db/Db.php';
require_once $path.'/vendor/skel/uri/Uri.php';
require_once $path.'/Content.php';
require_once $path.'/Post.php';
require_once $path.'/DataTraits.php';

class TestDb extends \Skel\Db implements \Skel\Interfaces\CmsDb {
  use \Skel\CmsDbSqlite;

  const VERSION = 1;

  protected function downgradeDatabase(int $targetVersion, int $fromVersion) {
    // Nothing to do here
  }

  protected function upgradeDatabase(int $targetVersion, int $fromVersion) {
    if ($fromVersion < 1 && $targetVersion >= 1) {
      $this->upgradeCmsDatabase(1, 0);
    }
  }

  public function getContentDir() {
    if (!$this->contentDir) throw new RuntimeException("You haven't set the content directory yet! Please set the directory by passing a valid URI to `setContentDir()`");
    return $this->contentDir;
  }

  protected function getFields(string $type) {
    $rawFields = \Skel\Content::getFields($type);
    $fields = array();
    foreach ($rawFields as $k => $def) $fields = "\"$k\"";
    return $fields;
  }

  public function setContentDir(\Skel\Interfaces\Uri $uri) {
    if ($uri->getScheme() != 'file') throw new RuntimeException("Sorry, don't know how to handle anything but a local folder! If you want this functionality, you'll have to implement it :(.");
    if (!is_dir($uri->getPath())) throw new RuntimeException("Provided URI doesn't point to a valid directory!");
    $this->contentDir = $uri;
    return $this;
  }
}

function getDb(bool $fresh=false) {
  $dir = 'tests/db';
  $file = 'test.sqlite3';
  if (!is_dir($dir)) throw new RuntimeException("It looks like we're either not in the right directory, or the $dir directory is not yet created. Please create that directory and run this script from the root directory.");

  if ($fresh && is_file("$dir/$file")) unlink("$dir/$file");

  $db = new TestDb("sqlite:$dir/$file");
  $db->setContentDir(new \Skel\Uri('file://'.getcwd().'/tests/content/'));
  return $db;
}


?>
