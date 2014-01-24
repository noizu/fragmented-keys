<?php
use \NoizuLabs\FragmentedKeys;
use \NoizuLabs\FragmentedKeys\Tag;
use \NoizuLabs\FragmentedKeys\Key;

class FragmentedKeysTest extends PHPUnit_Framework_TestCase {
    const OneTick = 1;
    private $tagNameA;
    private $tagNameB;
    private $tagNameAEntityOne;
    private $tagNameAEntityTwo;

    public function setUp()
    {
        global $container;

        if(!isset($container)) {
             $container = new \Pimple();
        }

        if(!isset($container['memcache'])) {
             $m = new \Memcache;
             $conn = $m->connect('127.0.0.1', '11211');
             $container['memcache'] = $m;
        }



        $time = new DateTime();
        $this->tagNameA = "TagA_" . $time->getTimestamp();
        $this->tagNameB = "TagB_" . $time->getTimestamp();
        $this->tagNameAEntityOne = 1;
        $this->tagNameAEntityTwo = 2;
    }

    private function WaitForClockTick()
    {
        sleep(1);
    }

    /**
     * @test
     */
    public function FragmentedTagShouldReturnTheSameValueIfIncrementHasNotBeenCalled()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $version1 = $tag->getFullTag();

        $this->WaitForClockTick();

        $tag2 = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $version2 = $tag2->getFullTag();

        $this->assertEquals($version1, $version2, "The second call to getVersion did not return the expected value");
    }

    /**
     * @test
     */
    public function FragmentedTagShouldReturnDifferentValuesIfIncrementHasBeenCalled()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $version1 = $tag->getFullTag();
        $tag->Increment();

        $tag2 = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $version2 = $tag2->getFullTag();

        $this->assertNotEquals($version1, $version2, "The second call to getVersion should not have matched the previous value");
    }

    /**
     * @test
     */
    public function FragmentedTagsShouldReturnDifferentValuesForDifferentEntities()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);

        $tag2 = new Tag\Standard($this->tagNameA, $this->tagNameAEntityTwo);

        $this->assertNotEquals($tag->getFullTag(), $tag2->getFullTag(), "key2 and key1 should have had different tag values");
    }

    /**
     * @test
     */
    public function FragmentedTagsShouldReturnDifferentValuesForDifferentTags()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);

        $tag2 = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne);

        $this->assertNotEquals($tag->getFullTag(), $tag2->getFullTag(), "key2 and key1 should have had different tag values");
    }

    /**
     * @test
     */
    public function CallingIncrementVersionShouldChangeTheVersionOnTheTagInstance()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $version = $tag->getTagVersion();
        $tag->Increment();
        $version2 = $tag->getTagVersion();

        $this->assertNotEquals($version,$version2);
    }

    /**
     * @test
     */
    public function IncrementingOneTagEntityShouldNotChangeVersionOfSameTagForADifferentEntity()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $tag2 = new Tag\Standard($this->tagNameA, $this->tagNameAEntityTwo);
        $version = $tag->getFullTag();
        $tag2->Increment();
        $this->assertEquals($version, $tag->getFullTag());
    }


    /**
     * @test
     */
    public function IncrementingOneTagEntityShouldNotChangeVersionOfDifferentTagForTheSameEntityId()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $tag2 = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne);
        $version = $tag->getFullTag();
        $tag2->Increment();
        $this->assertEquals($version, $tag->getFullTag());
    }

    /**
     * @test
     */
    public function GetKeyShouldCorrectlyPullVersionsFromAllTags()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $tag2 = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne);
        $theKey = new Key\Standard("ThisIsAKey", "" , array($tag,$tag2));

        $expected = md5("ThisIsAKey_:t" . $tag->getFullTag() . ":t" . $tag2->getFullTag());
        $this->assertEquals($expected, $theKey->getKey());
    }

    /**
     * @test
     */
    public function GetKeyShouldWorkWithASingleTag()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $theKey = new Key\Standard("ThisIsAKey", "" , array($tag));

        $expected = md5("ThisIsAKey_:t" . $tag->getFullTag());
        $this->assertEquals($expected, $theKey->getKey());
    }

    /**
     * @test
     */
    public function GetKeyShouldReturnSameValueOnSubsequentCallsIfTagsAreNotIncremented()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $tag2 = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne);
        $theKey = new Key\Standard("ThisIsAKey", "" , array($tag,$tag2));

        $firstKey = $theKey->getKey();

        $this->WaitForClockTick();

        $theKey = new Key\Standard("ThisIsAKey", "" , array($tag,$tag2));
        $secondKey = $theKey->getKey();

        $this->assertEquals($firstKey, $secondKey);
    }

    /**
     * @test
     */
    public function GetKeyShouldReturnDifferentValuesIfTagsAreIncremented()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $tag2 = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne);
        $theKey = new Key\Standard("ThisIsAKey", "" , array($tag,$tag2));
        $firstKey = $theKey->getKey();
        $tag2b = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne);
        $tag2b->Increment();
        $theKey = new Key\Standard("ThisIsAKey", "" , array($tag,$tag2));
        $secondKey = $theKey->getKey();
        $this->assertNotEquals($firstKey, $secondKey);
    }

}
