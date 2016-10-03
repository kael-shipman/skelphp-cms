<?php
namespace Skel;

/**
 * Page -- a more specific type of content that has a URI, a title, and an image, among other things
 */
class Page extends Content {
  public static function fromLocalizedUri(\Skel\DB $db, string $uri) {
    $page = new \Skel\Page($db);
    try {
      $pageData = $db->getPageByCanonicalUri(
        $db->getCanonicalUriFromLocalizedUri($uir)
      );
    } catch (Exception $e) {
      throw new InvalidContentException('Page at localized uri `'.$uri.'` not found!');
    }
    //TODO: Deserialize page data
    return $page;
  }

  public static function fromCanonicalUri(\Skel\DB $db, string $uri) {
    $page = new \Skel\Page($db);
    $pageData = $db->getPageByCanonicalUri($uri);
    if (!$pageData) throw new InvalidContentException('Page at canonical uri `'.$uri.'` not found!');
    //TODO: Deserialize page data
    return $page;
  }
}

?>
