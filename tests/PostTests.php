<?php

use PHPUnit\Framework\TestCase;

class PostTests extends TestCase {
  public function testCanCreateDefaultPost() {
    $p = new \Skel\Post();
    $this->assertTrue($p->getActive(), "Post should be 'active' by default");
    $this->assertNull($p->getAddress(), "Post should default to a null address");
    $this->assertEquals('Anonymous', $p->getAuthor(), "Post should return 'Anonymous' for null author");
    $this->assertNull($p->getCanonicalId(), "Post canonical id should default to null");
    $this->assertNull($p->getContent(), "Post should default to null");
    $this->assertEquals('post', $p->getContentClass(), "Post should default to a content class of 'content'");
    $this->assertEquals('text/plain; charset=UTF-8', $p->getContentType(), "Post type should default to 'text/plain; charset=UTF-8'");
    $this->assertNull($p->getContentUri(), "PostUri should default to null");
    $this->assertEquals((new \DateTime())->format('Y-m-d'), $p->getDateCreated()->format('Y-m-d'), "Post created date should default to today");
    $this->assertEquals((new \DateTime())->format('Y-m-d'), $p->getDateUpdated()->format('Y-m-d'), "Post updated date should default to today");
    $this->assertNull($p->getDateExpired(), "Post expiration date should default to null");
    $this->assertFalse($p->hasImg(), "Post should default to not having an image");
    $this->assertNull($p->getImgPrefix(), "Post should default to null img prefix");
    $this->assertEquals('en', $p->getLang(), "Post language should default to english");
    $this->assertNull($p->getParentAddress(), "Post parent address should default to null");
    $this->assertNull($p->getSlug(), "Post slug should default to null");
    $this->assertEquals(array(), $p->getTags(), "Post tags should default to empty array");
    $this->assertNull($p->getTitle(), "Post title should default to null");

    $this->assertTrue(count($p->getErrors()) > 0, "Default content should have errors");
    $setBySystem = array('active', 'canonicalId', 'content', 'contentClass', 'contentType', 'contentUri', 'dateCreated', 'dateExpired', 'dateUpdated', 'id', 'lang', 'parentAddress', 'slug', 'title', 'author', 'hasImg', 'imgPrefix');
    $this->assertEquals($setBySystem, $p->getFieldsSetBySystem(), "Fields set by system aren't correct.", 0.0, 20, true);
  }







  // Utilities

  protected function getTestPostData($lang) {
    if ($lang == 'en') {
      return array(
        'active' => false,
        'author' => 'Test McTesterson',
        'canonicalId' => '/writings/little-bo-peep',
        'content' => 'Little Bo Peep',
        'contentClass' => 'post',
        'contentType' => 'text/html; charset=UTF-8',
        'contentUri' => 'file://pages/writings/little-bo-peep.md',
        'dateCreated' => '2016-11-01T18:00:00-0500',
        'dateExpired' => '2017-01-01T00:00:00-0500',
        'dateUpdated' => '2016-11-01T18:00:00-0500',
        'hasImg' => true,
        'id' => 1,
        'imgPrefix' => '2016-11-little-bo-peep',
        'lang' => 'en',
        'parentAddress' => '/writings',
        'setBySystem' => array('canonicalId', 'contentClass', 'contentUri', 'id', 'imgPrefix', 'slug'),
        'slug' => 'little-bo-peep',
        'tags' => array('culture', 'economics'),
        'title' => 'Little Bo Peep',
      );
    } else {
      return array(
        'active' => false,
        'author' => 'Test McTesterson',
        'canonicalId' => '/writings/little-bo-peep',
        'content' => 'Señorita Bo Peep',
        'contentClass' => 'post',
        'contentType' => 'text/html; charset=UTF-8',
        'contentUri' => 'file://pages/writings/little-bo-peep.md',
        'dateCreated' => '2016-11-01T18:00:00-0500',
        'dateExpired' => '2017-01-01T00:00:00-0500',
        'dateUpdated' => '2016-11-01T18:00:00-0500',
        'hasImg' => true,
        'id' => 1,
        'imgPrefix' => '2016-11-little-bo-peep',
        'lang' => 'es',
        'parentAddress' => '/escritura',
        'setBySystem' => array('contentClass', 'contentUri', 'id'),
        'slug' => 'test',
        'tags' => array('culture', 'economics'),
        'title' => 'Señorita Bo Peep',
      );
    }
  }
}







