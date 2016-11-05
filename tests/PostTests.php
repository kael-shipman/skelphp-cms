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
    $this->assertNull($p->getDateExpired(), "Post expiration date should default to null");
    $this->assertEquals((new \DateTime())->format('Y-m-d'), $p->getDateUpdated()->format('Y-m-d'), "Post updated date should default to today");
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

  public function testCanCreatePostFromData() {
    $data = $this->getTestPostData('en');
    $p = new \Skel\Post($data);

    $this->assertEquals((bool)$data['active'], $p->getActive(), "Active wasn't set correctly");
    $this->assertEquals($data['parentAddress'].'/'.$data['slug'], $p->getAddress(), "Address isn't correct");
    $this->assertEquals($data['author'], $p->getAuthor(), "Author wasn't set correctly");
    $this->assertEquals($data['canonicalId'], $p->getCanonicalId(), "CanonicalId wasn't set correctly");
    $this->assertEquals($data['content'], $p->getContent(), "Content wasn't set correctly");
    $this->assertEquals($data['contentClass'], $p->getContentClass(), "Content class wasn't set correctly");
    $this->assertEquals($data['contentType'], $p->getContentType(), "Content Type wasn't set correctly");
    $this->assertEquals($data['contentUri'], $p->getContentUri()->toString(), "Content Uri wasn't set correctly");
    $this->assertEquals($data['dateCreated'], ($p->getDateCreated() ? $p->getDateCreated()->format(\DateTime::ISO8601) : null), "Date Created wasn't set correctly");
    $this->assertEquals($data['dateExpired'], ($p->getDateCreated() ? $p->getDateExpired()->format(\DateTime::ISO8601) : null), "Date Expired wasn't set correctly");
    $this->assertEquals($data['dateUpdated'], ($p->getDateCreated() ? $p->getDateUpdated()->format(\DateTime::ISO8601) : null), "Date Updated wasn't set correctly");
    $this->assertEquals((bool)$data['hasImg'], $p->hasImg(), "Has Img wasn't set correctly");
    $this->assertEquals($data['imgPrefix'], $p->getImgPrefix(), "Img Prefix wasn't set correctly");
    $this->assertEquals($data['lang'], $p->getLang(), "Language wasn't set correctly");
    $this->assertEquals($data['parentAddress'], $p->getParentAddress(), "Parent Address wasn't set correctly");
    $this->assertEquals($data['slug'], $p->getSlug(), "Slug wasn't set correctly");
    $this->assertEquals($data['tags'], $p->getTags(), "Tags weren't set correctly");
    $this->assertEquals($data['title'], $p->getTitle(), "Title wasn't set correctly");

    $this->assertEquals(array(), $p->getErrors(), "Errors weren't set correctly");
    $this->assertEquals($data['setBySystem'], $p->getFieldsSetBySystem(), "Fields set by system aren't correct.", 0.0, 20, true);
  }

  public function testCreateFromPartialDataThrowsException() {
    $data = $this->getTestPostData('en');
    for($i = 0; $i < count($data); $i++) {
      $test = $data;
      $k = array_keys($data)[$i];
      unset($test[$k]);

      $this->assertNotEquals($test, $data, "The test array should be an incomplete subset of the data array");

      try {
        $p = new \Skel\Post($test);
        $this->fail("Should have thrown an error with incomplete test data (missing key `$k`)");
      } catch (PHPUnit_Framework_AssertionFailedError $e) {
        throw $e;
      } catch (\Skel\InvalidDataException $e) {
        $this->assertTrue(true, "This is the desired behavior");
      }
    }
  }

  public function testNormalCreatePostWorkflow() {
    $data = $this->getTestPostData('en');
    $p = new \Skel\Post();

    $setBySystem = array('active', 'canonicalId', 'content', 'contentClass', 'contentType', 'contentUri', 'dateCreated', 'dateExpired', 'dateUpdated', 'id', 'lang', 'parentAddress', 'slug', 'title', 'author', 'hasImg', 'imgPrefix');
    $this->assertEquals($setBySystem, $p->getFieldsSetBySystem(), "Fields set by system are off.", 0.0, 10, true);
    
    // The basics
    $p
      ->setTitle($data['title'])
      ->setParentAddress($data['parentAddress'])
      ->setContent($data['content']);

    $this->assertEquals(array(), $p->getErrors(), "The given information should be enough to make a valid post");
    $this->assertEquals($data['slug'], $p->getSlug(), "Slug should have been automatically set from the title");
    $this->assertEquals($data['canonicalId'], $p->getCanonicalId(), "Canonical Id should have been automatically set from title and parent");
    $this->assertEquals($data['contentUri'], $p->getContentUri()->toString(), "Content Uri should have been automatically set");
    $this->assertEquals((new DateTime())->format('Y-m-').'little-bo-peep', $p->getImgPrefix(), "Img Prefix should have been automatically set using today's date.");

    $setBySystem = array('active', 'canonicalId', 'contentClass', 'contentType', 'contentUri', 'dateCreated', 'dateExpired', 'dateUpdated', 'id', 'lang', 'slug', 'author', 'hasImg', 'imgPrefix');
    $this->assertEquals($setBySystem, $p->getFieldsSetBySystem(), "Fields set by system are off.", 0.0, 10, true);

    // Add some more stuff
    $p
      ->setAuthor($data['author'])
      ->setContentType($data['contentType'])
      ->setHasImg(true)
      ->setDateCreated(\DateTime::createFromFormat(\DateTime::ISO8601, $data['dateCreated']))
    ;

    $this->assertEquals(array(), $p->getErrors(), "The given information should be enough to make a valid post");
    $this->assertEquals($data['author'], $p->getAuthor(), "Author wasn't set correctly");
    $this->assertEquals($data['contentType'], $p->getContentType(), "Content Type wasn't set correctly");
    $this->assertTrue($p->hasImg(), "Has Img wasn't set correctly");
    $this->assertEquals($data['dateCreated'], ($p->getDateCreated() ? $p->getDateCreated()->format(\DateTime::ISO8601) : null), "Date Created wasn't set correctly");
    $this->assertEquals($data['imgPrefix'], $p->getImgPrefix(), "Image Prefix wasn't set correctly on date change");

    $setBySystem = array('active', 'canonicalId', 'contentClass', 'contentUri', 'dateExpired', 'dateUpdated', 'id', 'lang', 'slug', 'imgPrefix');
    $this->assertEquals($setBySystem, $p->getFieldsSetBySystem(), "Fields set by system are off.", 0.0, 10, true);
  }

  public function testTriggers() {
    $data = $this->getTestPostData('en');
    $p = new \Skel\Post();

    // Changing title or date created should update img prefix
    $p->setTitle('test');
    $this->assertEquals((new \DateTime())->format('Y-m-').'test', $p->getImgPrefix(), "Changing title should have updated image prefix");
    $newDate = \DateTime::createFromFormat(\DateTime::ISO8601, $data['dateCreated']);
    $p->setDateCreated($newDate);
    $this->assertEquals($newDate->format('Y-m-').'test', $p->getImgPrefix(), "Changing date created should have updated image prefix");

    // Now set imgprefix manually and verify that no changes occur
    $imgPrefix = '1910-01-major-news';
    $p->setImgPrefix($imgPrefix);
    $p->setTitle('New Test');
    $this->assertEquals($imgPrefix, $p->getImgPrefix(), "Img Prefix shouldn't change now that we've set it manually");
    $p->setDateCreated(new DateTime());
    $this->assertEquals($imgPrefix, $p->getImgPrefix(),  "Img Prefix shouldn't change now that we've set it manually");
  }





  // Utilities

  protected function getTestPostData($lang) {
    if ($lang == 'en') {
      return array(
        'active' => 0,
        'author' => 'Test McTesterson',
        'canonicalId' => '/writings/little-bo-peep',
        'content' => 'Little Bo Peep',
        'contentClass' => 'post',
        'contentType' => 'text/html; charset=UTF-8',
        'contentUri' => 'file://pages/writings/little-bo-peep.md',
        'dateCreated' => '2016-01-01T18:00:00-0500',
        'dateExpired' => '2017-01-01T00:00:00-0500',
        'dateUpdated' => '2016-04-01T18:00:00-0500',
        'hasImg' => 1,
        'id' => 1,
        'imgPrefix' => '2016-01-little-bo-peep',
        'lang' => 'en',
        'parentAddress' => '/writings',
        'setBySystem' => array('canonicalId', 'contentClass', 'contentUri', 'id', 'imgPrefix', 'slug'),
        'slug' => 'little-bo-peep',
        'tags' => array('culture', 'economics'),
        'title' => 'Little Bo Peep',
      );
    } else {
      return array(
        'active' => 0,
        'author' => 'Test McTesterson',
        'canonicalId' => '/writings/little-bo-peep',
        'content' => 'Señorita Bo Peep',
        'contentClass' => 'post',
        'contentType' => 'text/html; charset=UTF-8',
        'contentUri' => 'file://pages/writings/little-bo-peep.md',
        'dateCreated' => '2016-11-01T18:00:00-0500',
        'dateExpired' => '2017-01-01T00:00:00-0500',
        'dateUpdated' => '2016-11-01T18:00:00-0500',
        'hasImg' => 1,
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







