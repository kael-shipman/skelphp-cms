<?php

$path = '.';

require_once $path.'/vendor/autoload.php';
require_once $path.'/vendor/skel/interfaces/Exceptions.php';
require_once $path.'/vendor/skel/interfaces/Interfaces.php';
require_once $path.'/vendor/skel/db/Db.php';
require_once $path.'/vendor/skel/uri/Uri.php';
require_once $path.'/Content.php';
require_once $path.'/Page.php';
require_once $path.'/Post.php';
require_once $path.'/Cms.php';

class Benchmark {
  protected static $checkpoints = array();

  public static function check(string $msg) {
    static::$checkpoints[] = microtime(true);
    $n = count(static::$checkpoints)-1;
    if ($n == 0) echo "\n$msg: Init";
    else {
      $time = static::$checkpoints[$n] - static::$checkpoints[$n-1];
      if ($time > 60) $time = floor($time/60).'m '.round($time%60, 2).'s';
      else $time = round($time,2).'s';
      echo "\n$msg: ".$time;
    }
  }
}

class Config {
  protected $config = array();
  function checkConfig() { return true; }
  function __construct(string $basefile) { return $this; }
  function get(string $key, string $default=null) { return $this->config[$key] ?: $default; }
  function set(string $key, $val) { $this->config[$key] = $val; return $this; }
  function dump() { return true; }
  function getDbPdo() { return 'sqlite:tests/db/test.sqlite3'; }
  function getDbContentRoot() { return 'tests/content'; }
}

function getDb(bool $fresh=false) {
  $dir = 'tests/db';
  $file = 'test.sqlite3';
  if (!is_dir($dir)) throw new RuntimeException("It looks like we're either not in the right directory, or the $dir directory is not yet created. Please create that directory and run this script from the root directory.");

  if ($fresh && is_file("$dir/$file")) unlink("$dir/$file");

  $db = new \Skel\Cms(new Config('tests/config'));
  return $db;
}


//Benchmark::check("Starting");

?>
