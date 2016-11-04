<?php

use PHPUnit\Framework\TestCase;

class ContentClassTests extends TestCase {
  public function testCanCreateDefaultContent() {
    $c = new \Skel\Content();
    $this->assertTrue($c->getActive(), "Content should be 'active' by default");
    $this->assertNull($c->getAddress(), "Content should default to a null address");
    $this->assertNull($c->getCanonicalId(), "Content canonical id should default to null");
    $this->assertNull($c->getContent(), "Content should default to null");
    $this->assertEquals('content', $c->getContentClass(), "Content should default to a content class of 'content'");
    $this->assertEquals('text/plain; charset=UTF-8', $c->getContentType(), "Content type should default to 'text/plain; charset=UTF-8'");
    $this->assertNull($c->getContentUri(), "ContentUri should default to null");
    $this->assertEquals((new \DateTime())->format('Y-m-d'), $c->getDateCreated()->format('Y-m-d'), "Content created date should default to today");
    $this->assertEquals((new \DateTime())->format('Y-m-d'), $c->getDateUpdated()->format('Y-m-d'), "Content updated date should default to today");
    $this->assertNull($c->getDateExpired(), "Content expiration date should default to null");
    $this->assertEquals('en', $c->getLang(), "Content language should default to english");
    $this->assertNull($c->getParentAddress(), "Content parent address should default to null");
    $this->assertNull($c->getSlug(), "Content slug should default to null");
    $this->assertEquals(array(), $c->getTags(), "Content tags should default to empty array");
    $this->assertNull($c->getTitle(), "Content title should default to null");

    $this->assertTrue(count($c->getErrors()) > 0, "Default content should have errors");
    $setBySystem = array('active', 'canonicalId', 'content', 'contentClass', 'contentType', 'contentUri', 'dateCreated', 'dateExpired', 'dateUpdated', 'id', 'lang', 'parentAddress', 'slug', 'title');
    $this->assertEquals($setBySystem, $c->getFieldsSetBySystem(), "Fields set by system aren't correct.", 0.0, 20, true);
  }

  public function testCanCreateBasicNewContent() {
    $data = $this->getTestContentData();
    $c = new \Skel\Content();
    $c
      ->setTitle($data['title'])
      ->setContent($data['content']);

    $this->assertEquals(\Skel\Content::createSlug($c->getTitle()), $c->getSlug(), "Slug was not automatically set with title");
    $this->assertTrue($c->fieldSetBySystem('slug'), "Slug should have been marked as set by system");
    $this->assertNull($c->getContentUri(), "The contentURI should still be null because no parent has been set yet.");

    $c->setParentAddress($data['parentAddress']);

    $this->assertEquals($data['parentAddress'].'/'.$c->getSlug(), $c->getCanonicalId(), "Canonical Id wasn't set correctly");
    $this->assertTrue($c->fieldSetBySystem('canonicalId'), "Canonical Id should have been marked as set by system");
    $this->assertEquals($c->getCanonicalId(), $c->getAddress(), "Address should be the same as the canonical Id if not set otherwise");
    $this->assertEquals('file://pages'.$c->getAddress().'.md', ($c->getContentUri() ? $c->getContentUri()->toString() : null), "Content Uri should be set when both slug and parentAddress are set");

    $c->addTag('culture');
    $c->addTag('reason');
    $this->assertEquals(array('culture','reason'), $c->getTags(), "Tags weren't set correctly");

    $setBySystem = array('active', 'canonicalId', 'contentClass', 'contentType', 'contentUri', 'dateCreated', 'dateExpired', 'dateUpdated', 'id', 'lang', 'slug');
    $this->assertEquals($setBySystem, $c->getFieldsSetBySystem(), "Fields set by system aren't correct.", 0.0, 20, true);

    $this->assertTrue(count($c->getErrors()) == 0, "Valid content shouldn't have errors");
  }

  public function testCanCreateComplexNewContent() {
    $data = $this->getTestContentData();
    $c = new \Skel\Content();
    $c
      ->setTitle($data['title'])
      ->setActive($data['active'])
      ->setParentAddress($data['parentAddress'])
      ->setCanonicalId($data['canonicalId'])
      ->setContent($data['content'])
      ->setContentType($data['contentType'])
      ->setContentUri(new \Skel\Uri($data['contentUri']))
      ->setDateCreated(\DateTime::createFromFormat(\DateTime::ISO8601, $data['dateCreated']))
      ->setDateUpdated(\DateTime::createFromFormat(\DateTime::ISO8601, $data['dateUpdated']))
      ->setDateExpired(\DateTime::createFromFormat(\DateTime::ISO8601, $data['dateExpired']))
      ->setLang($data['lang'])
      ->setSlug($data['slug'])
      ->addTag($data['tags'][0])
      ->addTag($data['tags'][1])
    ;

    $this->assertFalse($c->getActive(), "Active should have been set to false");
    $this->assertEquals($data['parentAddress'].'/'.$data['slug'], $c->getAddress(), "Address is not correct");
    $this->assertEquals($data['canonicalId'], $c->getCanonicalId(), "Canonical Id was not set correctly");
    $this->assertEquals($data['content'], $c->getContent(), "Content was not set correctly");
    $this->assertEquals('content', $c->getContentClass(), "Content class was not set correctly");
    $this->assertEquals($data['contentType'], $c->getContentType(), "Content type was not set correctly");
    $this->assertEquals($data['contentUri'], ($c->getContentUri() ? $c->getContentUri()->toString() : null), "Content Uri was not set correctly");
    $this->assertEquals($data['dateCreated'], $c->getDateCreated()->format(\DateTime::ISO8601), "Date created was not set correctly");
    $this->assertEquals($data['dateExpired'], $c->getDateExpired()->format(\DateTime::ISO8601), "Date expired was not set correctly");
    $this->assertEquals($data['dateUpdated'], $c->getDateUpdated()->format(\DateTime::ISO8601), "Date Updated was not set correctly");
    $this->assertEquals($data['lang'], $c->getLang(), "Language was not set correctly");
    $this->assertEquals($data['parentAddress'], $c->getParentAddress(), "Parent address not set correctly");
    $this->assertEquals($data['slug'], $c->getSlug(), "Slug not set correctly");
    $this->assertEquals($data['tags'], $c->getTags(), "Tags were not set correctly");
    $this->assertEquals($data['title'], $c->getTitle(), "Title was not set correctly");

    $this->assertTrue(count($c->getErrors()) == 0, "Content created from data shouldn't have errors");
    $this->assertTrue(count($c->getChanges()) > 0, "Content created should have all fields marked as changed");

    $setBySystem = array('contentClass','id');
    $this->assertEquals($setBySystem, $c->getFieldsSetBySystem(), "Content shouldn't reflect any fields set by the system");
  }

  public function testCanCreateContentFromData() {
    $data = $this->getTestContentData();
    $c = new \Skel\Content($data);

    $this->assertFalse($c->getActive(), "Active should have been set to false");
    $this->assertEquals($data['parentAddress'].'/'.$data['slug'], $c->getAddress(), "Address is not correct");
    $this->assertEquals($data['canonicalId'], $c->getCanonicalId(), "Canonical Id was not set correctly");
    $this->assertEquals($data['content'], $c->getContent(), "Content was not set correctly");
    $this->assertEquals('content', $c->getContentClass(), "Content class was not set correctly");
    $this->assertEquals($data['contentType'], $c->getContentType(), "Content type was not set correctly");
    $this->assertEquals($data['contentUri'], ($c->getContentUri() ? $c->getContentUri()->toString() : null), "Content Uri was not set correctly");
    $this->assertEquals($data['dateCreated'], $c->getDateCreated()->format(\DateTime::ISO8601), "Date created was not set correctly");
    $this->assertEquals($data['dateExpired'], $c->getDateExpired()->format(\DateTime::ISO8601), "Date expired was not set correctly");
    $this->assertEquals($data['dateUpdated'], $c->getDateUpdated()->format(\DateTime::ISO8601), "Date Updated was not set correctly");
    $this->assertEquals($data['lang'], $c->getLang(), "Language was not set correctly");
    $this->assertEquals($data['parentAddress'], $c->getParentAddress(), "Parent address not set correctly");
    $this->assertEquals($data['slug'], $c->getSlug(), "Slug not set correctly");
    $this->assertEquals($data['tags'], $c->getTags(), "Tags were not set correctly");
    $this->assertEquals($data['title'], $c->getTitle(), "Title was not set correctly");

    $this->assertTrue(count($c->getErrors()) == 0, "Content created from data shouldn't have errors");
    $this->assertTrue(count($c->getChanges()) == 0, "Content created from data shouldn't have changes");

    $this->assertEquals(array('contentClass', 'id'), $c->getFieldsSetBySystem(), "All pertinent fields were set by the user, so there should be no fields set by the system.");
  }






  // Utilities

  protected function getTestContentData() {
    return array(
      'active' => false,
      'canonicalId' => '/about/test',
      'content' => 'Little Bo Peep',
      'contentClass' => 'content',
      'contentType' => 'text/html; charset=UTF-8',
      'contentUri' => 'file://pages/about/test2.md',
      'dateCreated' => '2016-11-01T18:00:00-0500',
      'dateExpired' => '2017-01-01T00:00:00-0500',
      'dateUpdated' => '2016-11-01T18:00:00-0500',
      'id' => 1,
      'lang' => 'es',
      'parentAddress' => '/about',
      'setBySystem' => array('contentClass', 'id'),
      'slug' => 'test',
      'tags' => array('culture', 'economics'),
      'title' => 'About Test',
    );
  }
}

