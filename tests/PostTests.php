<?php

use PHPUnit\Framework\TestCase;

class PostTest extends TestCase {
  public function testCanCreateDefaultPost() {
    try {
      $c = new \Skel\Post();
      $this->assertTrue($c->getActive(), "Content should be 'active' by default");
      $this->assertNull($c->getTitle(), "Content title should default to null");
      $this->assertNull($c->getCanonicalAddress(), "Content canonical id should default to null");
      $this->assertEquals('content', $c->getContentClass(), "Content class should default to 'content'");
      $this->assertEquals('text/plain; charset=UTF-8', $c->getContentType(), "Content type should default to 'text/plain; charset=UTF-8'");
      $this->assertNull($c->getContentUri(), "Content Uri should default to null");
      $this->assertEquals((new DateTime())->format('Y-m-d'), $c->getDateCreated()->format('Y-m-d'), "Content created date should default to today");
      $this->assertEquals((new DateTime())->format('Y-m-d'), $c->getDateUpdated()->format('Y-m-d'), "Content updated date should default to today");
      $this->assertNull($c->getDateExpired(), "Content expiration date should default to null");
      $this->assertEquals('en', $c->getLang(), "Content language should default to english");
      $this->assertEquals('', $c->getContent(), "Content should default to empty string");
      $this->assertEquals(array(), $c->getAttributes(), "Content attributes should default to empty array");
      $this->assertEquals(array(), $c->getTags(), "Content tags should default to empty array");
      $this->assertEquals(array(), $c->getAddresses(), "Content addresses should default to empty array");
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
      throw $e;
    } catch (Exception $e) {
      $this->fail("Shouldn't throw exception on create empty object");
    }
  }

  public function testCanCreateFullContent() {
    try {
      $c = $this->getTestContent();
      $this->assertFalse($c->getActive(), "Active should have been set to false");
      $this->assertEquals('Test', $c->getTitle(), "Title should have been set to 'Test'");
      $this->assertEquals('/about/me', $c->getCanonicalAddress(), "Canonical Id Should have been set to '/about/me'");
      $this->assertEquals('content', $c->getContentClass(), "Content class should be 'content'");
      $this->assertEquals('text/markdown; charset=UTF-8', $c->getContentType(), "Content type should be 'text/markdown; charset=UTF-8'");
      $this->assertEquals('file://pages/about/me.md', $c->getContentUri()->toString(), "Content URI was not set correctly");
      $this->assertEquals("2016-11-01T18:00:00-0500", $c->getDateCreated()->format(DateTime::ISO8601), "Date created was not set correctly");
      $this->assertEquals("2016-11-01T18:00:00-0500", $c->getDateUpdated()->format(DateTime::ISO8601), "Date Updated was not set correctly");
      $this->assertEquals("2017-01-01T00:00:00-0500", $c->getDateExpired()->format(DateTime::ISO8601), "Date expired was not set correctly");
      $this->assertEquals('es', $c->getLang(), "Language was not set correctly");
      $this->assertEquals('Little Bo Peep', $c->getContent(), "Content was not set correctly");
      $this->assertEquals(array('test' => 'yes', 'permissions' => 7), $c->getAttributes(), "Attributes were not set correctly");
      $this->assertEquals(array('writings', 'economics'), $c->getTags(), "Tags were not set correctly");
      $this->assertEquals(array('/about/me', '/writings/about-me'), $c->getAddresses(), "Addresses were not set correctly");
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
      throw $e;
    } catch (Exception $e) {
      $this->fail("Should be able to alter all fields of a content object");
    }
  }

  public function testThrowsExceptionOnSaveWithoutDb() {
    try {
      $c = $this->getTestContent();
      $c->save();
      $this->fail("Should have thrown exception on save without db");
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
      throw $e;
    } catch (Exception $e) {
      $this->assertTrue(true);
    }
  }

  public function testCanAttachDatasourceToContent() {
    try {
      $c = new \Skel\Content();
      $c->setDatasource(getDb());
      $this->assertTrue(true);
    } catch (Exception $e) {
      $this->fail("Should be able to attach a CmsDb to the content object");
    }
  }

  public function testCantSaveWithDefaults() {
    try {
      $db = getDb();
      $c = new \Skel\Content();
      $c->setDatasource($db);
      $c->save();
      $this->fail("Should have thrown exception on save with defaults");
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
      throw $e;
    } catch (RuntimeException $e) {
      $this->assertTrue(true);
    }
  }

  public function testCanSaveNewContent() {
    try {
      $db = getDb();
      $c = $this->getTestContent();
      $c->setDatasource($db);
      $c->save();
    } catch (Exception $e) {
      $this->fail("Should be able to save new content :(");
    }

    $data = $db->getContentDataWhere('"title" = ?', array('Test'));

    $this->assertFalse($data['active'], "Active should have been set to false");
    $this->assertEquals('Test', $data['title'], "Title should have been set to 'Test'");
    $this->assertEquals('/about/me', $data['canonical_address'], "Canonical Id Should have been set to '/about/me'");
    $this->assertEquals('content', $data['content_class'], "Content class should be 'content'");
    $this->assertEquals('text/markdown; charset=UTF-8', $data['content_type'], "Content type should be 'text/markdown; charset=UTF-8'");
    $this->assertEquals('file://pages/about/me.md', $data['content_uri'], "Content URI was not set correctly");
    $this->assertEquals("2016-11-01T18:00:00-0500", $data['date_created'], "Date created was not set correctly");
    $this->assertEquals("2016-11-01T18:00:00-0500", $data['date_updated'], "Date Updated was not set correctly");
    $this->assertEquals("2017-01-01T00:00:00-0500", $data['date_expired'], "Date expired was not set correctly");
    $this->assertEquals('es', $data['lang'], "Language was not set correctly");
    $this->assertEquals('Little Bo Peep', $data['content'], "Content was not set correctly");
    $this->assertEquals(array('test' => 'yes', 'permissions' => 7), $data['attributes'], "Attributes were not set correctly");
    $this->assertEquals(array('writings', 'economics'), $data['tags'], "Tags were not set correctly");
    $this->assertEquals(array('/about/me', '/writings/about-me'), $data['addresses'], "Addresses were not set correctly");
  }










  // Utilities

  protected function getTestContent() {
    $c = new \Skel\Content();
    $c
      ->setActive(false)
      ->setTitle('Test')
      ->setCanonicalAddress('/about/me')
      ->setContentClass('content')
      ->setContentType('text/markdown; charset=UTF-8')
      ->setContentUri(new \Skel\Uri("file://pages/about/me.md"))
      ->setDateCreated(DateTime::createFromFormat(DateTime::ISO8601, '2016-11-01T18:00:00-0500')
      ->setDateExpired(DateTime::createFromFormat(DateTime::ISO8601, '2017-01-01T00:00:00-0500')
      ->setDateUpdated(DateTime::createFromFormat(DateTime::ISO8601, '2016-11-01T18:00:00-0500')
      ->setLang('es')
      ->setContent('Little Bo Peep')
      ->setAttribute('test', 'yes')
      ->setAttribute('permissions', 7)
      ->setTag('writings')
      ->setTag('economics')
      ->addAddress('/about/me')
      ->addAddress('/writings/about-me')
    ;
  }
}







