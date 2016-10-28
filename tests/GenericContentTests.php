<?php

use PHPUnit\Framework\TestCase;

getDb(true);

class GenericContentTests extends TestCase {
  public function testCanCreateDefaultContent() {
    try {
      $c = new \Skel\Content(getDb());
      $this->assertTrue($c->getActive(), "Content should be 'active' by default");
      $this->assertNull($c->getTitle(), "Content title should default to null");
      $this->assertNull($c->getCanonicalAddr(), "Content canonical id should default to null");
      $this->assertEquals('\Skel\Content', $c->getContentClass(), "Content class should default to '\Skel\Content'");
      $this->assertEquals('text/plain; charset=UTF-8', $c->getContentType(), "Content type should default to 'text/plain; charset=UTF-8'");
      $this->assertNull($c->getContentUri(), "Content Uri should default to null");
      $this->assertEquals((new \DateTime())->format('Y-m-d'), $c->getDateCreated()->format('Y-m-d'), "Content created date should default to today");
      $this->assertEquals((new \DateTime())->format('Y-m-d'), $c->getDateUpdated()->format('Y-m-d'), "Content updated date should default to today");
      $this->assertNull($c->getDateExpired(), "Content expiration date should default to null");
      $this->assertNull($c->getLang(), "Content language should default to null (which is invalid)");
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
      $this->assertEquals('/about/me', $c->getCanonicalAddr(), "Canonical Address Should have been set to '/about/me'");
      $this->assertEquals('Content', $c->getContentClass(), "Content class should be 'Content'");
      $this->assertEquals('text/markdown; charset=UTF-8', $c->getContentType(), "Content type should be 'text/markdown; charset=UTF-8'");
      $this->assertEquals('file://pages/about/me.md', $c->getContentUri()->toString(), "Content URI was not set correctly");
      $this->assertEquals("2016-11-01T18:00:00-0500", $c->getDateCreated()->format(\DateTime::ISO8601), "Date created was not set correctly");
      $this->assertEquals("2016-11-01T18:00:00-0500", $c->getDateUpdated()->format(\DateTime::ISO8601), "Date Updated was not set correctly");
      $this->assertEquals("2017-01-01T00:00:00-0500", $c->getDateExpired()->format(\DateTime::ISO8601), "Date expired was not set correctly");
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

  public function testCantSaveWithDefaults() {
    try {
      $db = getDb();
      $c = new \Skel\Content($db);
      $c->save();
      $this->fail("Should have thrown exception on save with defaults");
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
      throw $e;
    } catch (RuntimeException $e) {
      $this->assertTrue(true);
    }
  }

  public function testCanSaveNewContent() {
    $db = getDb(true);
    $c = $this->getTestContent();

    $this->assertNull($c->getId());

    try {
      $c->save();
    } catch (\Skel\InvalidContentException $e) {
      throw new \Skel\InvalidContentException(implode(" ", $db->getCmsErrors()));
    } catch (\Skel\InvalidDataException $e) {
      throw new \Skel\InvalidDataException(implode(" ", $c->getErrors()));
    }

    $data = $db->getContentDataWhere('"title" = ?', array('Test'));

    $this->assertTrue(count($data) == 1, "Database should only have returned one result for content with title 'Test'");

    $data = $data[0];

    $this->assertTrue(is_numeric($c->getId()), "Saving the Content should have set it's id to a numeric value");
    $this->assertEquals(0, $data['active'], "Active should have been set to false");
    $this->assertEquals('Test', $data['title'], "Title should have been set to 'Test'");
    $this->assertEquals('/about/me', $data['canonicalAddr'], "Canonical Id Should have been set to '/about/me'");
    $this->assertEquals('\Skel\Content', $data['contentClass'], "Content class should be 'Content'");
    $this->assertEquals('text/markdown; charset=UTF-8', $data['contentType'], "Content type should be 'text/markdown; charset=UTF-8'");
    $this->assertEquals('file://pages/about/me.md', $data['contentUri'], "Content URI was not set correctly");
    $this->assertEquals("2016-11-01T18:00:00-0500", $data['dateCreated'], "Date created was not set correctly");
    $this->assertEquals("2016-11-01T18:00:00-0500", $data['dateUpdated'], "Date Updated was not set correctly");
    $this->assertEquals("2017-01-01T00:00:00-0500", $data['dateExpired'], "Date expired was not set correctly");
    $this->assertEquals('es', $data['lang'], "Language was not set correctly");
    $this->assertEquals('Little Bo Peep', $data['content'], "Content was not set correctly");
    $this->assertEquals(array('test' => 'yes', 'permissions' => 7), $data['attributes'], "Attributes were not set correctly");
    $this->assertEquals(array('writings', 'economics'), $data['tags'], "Tags were not set correctly");
    $this->assertEquals(array('/about/me', '/writings/about-me'), $data['addresses'], "Addresses were not set correctly");
  }

  public function testCanCreateFromData() {
    $db = getDb(true);
    $c = $this->getTestContent();
    $c->save();
    $data = $db->getContentDataWhere('"title" = ?', array('Test'));

    $d = new \Skel\Content($db, $data[0]);

    $this->assertEquals($c->getId(), $d->getId(), "Error getting Id from data");
    $this->assertEquals($c->getActive(), $d->getActive(), "Error getting Active from data");
    $this->assertEquals($c->getTitle(), $d->getTitle(), "Error getting Title from data");
    $this->assertEquals($c->getCanonicalAddr(), $d->getCanonicalAddr(), "Error getting CanonicalAddr from data");
    $this->assertEquals($c->getContentClass(), $d->getContentClass(), "Error getting ContentClass from data");
    $this->assertEquals($c->getContentType(), $d->getContentType(), "Error getting ContentType from data");
    $this->assertEquals($c->getContentUri(), $d->getContentUri(), "Error getting ContentUri from data");
    $this->assertEquals($c->getDateCreated(), $d->getDateCreated(), "Error getting DateCreated from data");
    $this->assertEquals($c->getDateExpired(), $d->getDateExpired(), "Error getting DateExpired from data");
    $this->assertEquals($c->getDateUpdated(), $d->getDateUpdated(), "Error getting DateUpdated from data");
    $this->assertEquals($c->getLang(), $d->getLang(), "Error getting Lang from data");
    $this->assertEquals($c->getContent(), $d->getContent(), "Error getting Content from data");
    $this->assertEquals($c->getAttributes(), $d->getAttributes(), "Error getting Attributes from data");
    $this->assertEquals($c->getTags(), $d->getTags(), "Error getting Tags from data");
    $this->assertEquals($c->getAddresses(), $d->getAddresses(), "Error getting Addresses from data");
  }

  public function testCanGetAttributesByKey() {
    $c = $this->getTestContent();
    $this->assertEquals('default', $c->getAttribute('nonexistent', 'default'), "Should have returned the default value for a nonexistent attribute");
    $this->assertEquals(7, $c->getAttribute('permissions'), "Should have returned the permissions attribute that was set for the content");
  }









  // Utilities

  protected function getTestContent() {
    $c = new \Skel\Content(getDb());
    $c
      ->setActive(false)
      ->setTitle('Test')
      ->setCanonicalAddr('/about/me')
      ->setContentClass('\Skel\Content')
      ->setContentType('text/markdown; charset=UTF-8')
      ->setContentUri(new \Skel\Uri("file://pages/about/me.md"))
      ->setDateCreated(\DateTime::createFromFormat(\DateTime::ISO8601, '2016-11-01T18:00:00-0500'))
      ->setDateExpired(\DateTime::createFromFormat(\DateTime::ISO8601, '2017-01-01T00:00:00-0500'))
      ->setDateUpdated(\DateTime::createFromFormat(\DateTime::ISO8601, '2016-11-01T18:00:00-0500'))
      ->setLang('es')
      ->setContent('Little Bo Peep')
      ->setAttribute('test', 'yes')
      ->setAttribute('permissions', 7)
      ->addTag('writings')
      ->addTag('economics')
      ->addAddress('/about/me')
      ->addAddress('/writings/about-me')
    ;

    return $c;
  }
}






