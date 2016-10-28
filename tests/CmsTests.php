<?php

use PHPUnit\Framework\TestCase;

class CmsTests extends TestCase {
  protected $cms;
  protected $initialized;

  public function testCreateSimplePost() {
    //Benchmark::check("Begin CreateSimplePost");
    $cms = $this->getCms();
    //Benchmark::check("Got CMS");
    try {
      $post = $cms->newPost('Test1', 'music', 'en')
        ->setAuthor('Test McTesterson')
        ->setContent('Something')
      ;
      $post->save();
    } catch (\Skel\InvalidContentException $e) {
      throw new \Skel\InvalidContentException(implode(' ', $cms->getCmsErrors()));
    } catch (\Skel\InvalidDataException $e) {
      throw new \Skel\InvalidDataException(implode(' ', $post->getErrors()));
    }
    //Benchmark::check("Finished trying to create post");

    $test = $cms->getContentByAddress('/music/test1');
    //Benchmark::check("Got Content from db");
    $this->assertTrue($test !== null, "Content lookup by expected address `/music/test1` failed");
    $this->assertTrue($test instanceof \Skel\Post, "Content was returned using incorrect class");
    $this->assertEquals((new DateTime())->format('Y-m-').'test1', $test->getImgPrefix(), "ImgPrefix wasn't properly created");
    $this->assertEquals('file://pages/music/test1.md', $test->getContentUri()->toString(), "Content Uri wasn't set correctly");
    //Benchmark::check("Finished CreateSimplePost Tests");
  }

  public function testErrorOnCreateDuplicatePost() {
    //Benchmark::check("Begin ErrorOnCreateDuplicatePost");
    $cms = $this->getCms();

    try {
      $post = $cms->newPost('Test2', 'music', 'en')
        ->setAuthor('Test McTesterson')
        ->setContent('Something')
      ;
      $post->save();
    } catch (\Skel\InvalidContentException $e) {
      throw new \Skel\InvalidContentException(implode(' ', $cms->getCmsErrors()));
    } catch (\Skel\InvalidDataException $e) {
      throw new \Skel\InvalidDataException(implode(' ', $post->getErrors()));
    }

    try {
      $post = $cms->newPost('Test2', 'music', 'en')
        ->setAuthor('Test McTesterson')
        ->setContent('Something else')
      ;
      $post->save();
      $this->fail("Should have failed on inserting duplicate content");
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
      throw $e;
    } catch (\Skel\InvalidDataException $e) {
      $this->assertTrue(true);
    }
    //Benchmark::check("End ErrorOnCreateDuplicatePost");
  }

  public function testEnforceValidContentClasses() {
    //Benchmark::check("Begin EnforceValidContentClasses");
    $cms = $this->getCms();

    try {
      $test = $cms->newPost('Test3', 'music', 'en')->setContent('Teeeeeeest');
      $test->save();
    } catch (\Skel\InvalidContentException $e) {
      throw new \Skel\InvalidContentException(implode(' ', $cms->getCmsErrors()));
    } catch (\Skel\InvalidDataException $e) {
      throw new \Skel\InvalidDataException(implode(' ', $post->getErrors()));
    }

    $test->setContentClass('\NeoPost');
    try {
      $test->save();
      $this->fail("Should have thrown an error on save with invalid Content Class");
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
      throw $e;
    } catch (\Skel\InvalidDataException $e) {
      $this->assertTrue(true);
    }
    //Benchmark::check("End EnforceValidContentClasses");
  }

  public function testCantRetrieveContentWithInvalidContentClass() {
    //Benchmark::check("Begin Cant Retrieve Content With Invalid Content Class");
    $cms = $this->getCms();

    //Insert into db with bad content class
    $cms->exec('INSERT INTO "content" ("canonicalAddr", "contentClass", "contentUri", "dateCreated", "dateUpdated", "lang", "title") VALUES (\'/music/test4\', \'\NeoPost\', \'file://pages/music/test4.md\', \'2016-10-29T17:15:52-0000\', \'2016-10-29T17:15:52-0000\', \'en\', \'Test4\')');
    $id = $cms->lastInsertId();
    $cms->exec('INSERT INTO "content_addresses" ("contentId", "address") VALUES ('.$id.', \'/music/test4\')');

    // Try to get the content
    try {
      $test = $cms->getContentByAddress('/music/test4');
      $this->fail("Should have thrown error on content with disallowed contentClass");
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
      throw $e;
    } catch (\Skel\DisallowedContentClassException $e) {
      $this->assertTrue(true);
    }

    // Set NeoPost as valid content class and try again
    $cms->setValidContentClasses(array('\Skel\Content', '\Skel\Post', '\NeoPost'));
    try {
      $test = $cms->getContentByAddress('/music/test4');
      $this->fail("Should have thrown error on content with nonexistent contentClass");
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
      throw $e;
    } catch (\Skel\NonexistentContentClassException $e) {
      $this->assertTrue(true);
    }

    // Update content class to `NondescendentContentClass` (which is defined in bootstrap.php) and try again
    $cms->setValidContentClasses(array('\Skel\Content', '\Skel\Post', '\NondescendentContentClass'));
    $cms->exec('UPDATE "content" SET "contentClass" = \'\NondescendentContentClass\' WHERE "contentClass" = \'\NeoPost\'');
    try {
      $test = $cms->getContentByAddress('/music/test4');
      $this->fail("Should have thrown error on content with class that doesn't descend from Content");
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
      throw $e;
    } catch (\Skel\NondescendentContentClassException $e) {
      $this->assertTrue(true);
    }

    // Clean up
    $cms->exec('DELETE FROM "content" WHERE "contentClass" = \'NeoPost\'');
    //Benchmark::check("End Cant Retrieve Content With Invalid Content Class");
  }

  public function testGetMostRecentPosts() {
    //Benchmark::check("Begin GetMostRecentPosts");
    // Clear DB
    $this->cms = null;
    $this->initialized = null;
    $this->initializeData();
    $cms = $this->getCms();

    $posts = $cms->getPostIndex(null, 5);
    $this->assertEquals(5, count($posts));
    $this->assertEquals('Photo15', $posts[0]->getTitle());
    $this->assertEquals('Test15', $posts[2]->getTitle());
    $this->assertEquals('Photo12', $posts[4]->getTitle());
    //Benchmark::check("End GetMostRecentPosts");
  }

  public function testGetPostByAddress() {
    //Benchmark::check("Begin GetPostByAddress");
    $cms = $this->getCms();
    $cms->newPost('My First Post', 'photography', 'en')->setContent('something')->save();
    $post = $cms->getContentByAddress('/photography/my-first-post');
    $this->assertTrue($post instanceof \Skel\Post, "Couldn't get post by address");
    //Benchmark::check("End GetPostByAddress");
  }

  public function testGetPostIndex() {
    //Benchmark::check("Begin GetPostIndex");
    $this->initializeData();
    $cms = $this->getCms();

    $writingsIndex = $cms->getPostIndex('writings');
    $this->assertEquals(15, count($writingsIndex), "getPostIndex should have returned all writing posts if no limit was specified");
    $this->assertEquals('Test15', $writingsIndex[0]->getTitle(), "getPostIndex should have returned most recent post first");
    $this->assertEquals('Test1', $writingsIndex[14]->getTitle(), "getPostIndex should have returned oldest post last");
    //Benchmark::check("End GetPostByAddress");
  }

  public function testGetPagedPostIndex() {
    //Benchmark::check("Begin GetPagedPostByAddress");
    $this->initializeData();
    $cms = $this->getCms();

    $writingsIndex = $cms->getPostIndex('writings', 5);
    $this->assertEquals(5, count($writingsIndex), "getPostIndex should have returned the specified number of results");
    $this->assertEquals('Test15', $writingsIndex[0]->getTitle(), "getPostIndex should have returned most recent post of the first page first");
    $this->assertEquals('Test11', $writingsIndex[4]->getTitle(), "getPostIndex should have returned oldest post of the first page last");

    $writingsIndex = $cms->getPostIndex('writings', 5, 2);
    $this->assertEquals(5, count($writingsIndex), "getPostIndex should have returned the specified number of results");
    $this->assertEquals('Test10', $writingsIndex[0]->getTitle(), "getPostIndex should have returned most recent post of the second page first");
    $this->assertEquals('Test6', $writingsIndex[4]->getTitle(), "getPostIndex should have returned oldest post of the second page last");
    //Benchmark::check("End GetPagedPostByAddress");
  }












  protected function initializeData() {
    if ($this->initialized) return true;
    $cms = $this->getCms();
    $num = 15;
    for($i = 1; $i <= $num; $i++) {
      $dateCreated = new DateTime();
      $dateCreated->sub(new DateInterval('P'.(7*($num-($i-1))).'D'));
      $cms->newPost('Test'.$i, 'writings', 'en')
        ->setContent('This is Test'.$i)
        ->setDateCreated($dateCreated)
        ->setDateUpdated($dateCreated)
        ->save()
      ;
    }

    for($i = 1; $i <= $num; $i++) {
      $dateCreated = new DateTime();
      $dateCreated->sub(new DateInterval('P'.(3*($num-($i-1))).'D'));
      $cms->newPost('Photo'.$i, 'photography', 'en')
        ->setContent('This is Photography Test'.$i)
        ->setDateCreated($dateCreated)
        ->setDateUpdated($dateCreated)
        ->save()
      ;
    }
    $this->initialized = true;
  }

  protected function getCms() {
    if ($this->cms) return $this->cms;
    $this->cms = getDb(true);
    return $this->cms;
  }
}
