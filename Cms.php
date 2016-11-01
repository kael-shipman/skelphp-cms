<?php
namespace Skel;

class Cms implements Interfaces\Cms {

  public static function setValidContentClasses(array $classes) {
    $valid = array();
    foreach($classes as $k => $c) {
      if (is_numeric($k)) $valid[$c] = $c;
      else $valid[$k] = $c;
    }
    static::$validContentClasses = $valid;
  }

  protected function validateContentData(int $id=null, $newData) {
    $valid = true;
    if ($id) {
      $stm = $this->db->query('SELECT * FROM "content" WHERE "id" = '.$id);
      $data = $stm->fetchAll(\PDO::FETCH_ASSOC);
      $data = $this->attachAddressesToContent($data);
      $data = $this->attachAttributesToContent($data);
      $data = $this->attachContentToContent($data);
      $data = $this->attachTagsToContent($data);

      $data = array_replace($data[0], $newData);
    } else {
      $data = $newData;
    }

    // Validate ContentClass
    if (!array_key_exists($data['contentClass'], static::$validContentClasses)) {
      $this->setCmsError('contentClass', "The specified content class `$data[contentClass]` is not one of the currently designated valid content classes (`".implode('`, `', static::$validContentClasses)."`).");
      $valid = false;
    }

    // Validate Post Title and Category
    if ($data['contentClass'] == 'Post') {
      $stm = $this->db->prepare('SELECT "id" FROM "content" WHERE "title" = ? and "category" = ? and "id" != ?');
      $stm->execute(array($data['title'], $data['attributes']['category'], $id ?: 0));
      $rows = $stm->fetchAll(\PDO::FETCH_ASSOC);
      if (count($rows) > 0) {
        $this->setCmsError('title', 'There is already a post with the given title and category in the database. Please choose either a different title or a different category.');
        $valid = false;
      }
    }

    // Validate Addresses
    foreach($data['addresses'] as $a) {
      $stm = $this->db->prepare('SELECT "contentId" FROM "content_addresses" WHERE "address" = ? and "contentId" != ?');
      $stm->execute(array($a, $id ?: 0));
      $rows = $stm->fetchAll(\PDO::FETCH_ASSOC);
      if (count($rows) > 0) {
        $this->setCmsError('addresses', 'The address you specified for this content ('.$a.') is already being used by other content.');
        $valid = false;
      }
    }

    return $valid;
  }

}

