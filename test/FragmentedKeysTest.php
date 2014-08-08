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
    private $apcHandler;
    private $container;
    
    //==================================================================================================================
    // SetUp/Teardown
    //==================================================================================================================
    public function setUp()
    {
        global $container;
        $this->container = &$container;
        
        if(!isset($container)) {
             $container = new \Pimple();
        }

        if(!isset($container['memcache'])) {
             $m = new \Memcached;
             $conn = $m->addServer('127.0.0.1', '11211');
             $container['memcache'] = $m;
        }

        if(extension_loaded('apc') && ini_get('apc.enabled') && ini_get('apc.enable_cli'))
        {
            $this->apcHandler = new \NoizuLabs\FragmentedKeys\CacheHandler\Apc();
        } 

        $time = new DateTime();
        $this->tagNameA = "TagA_" . $time->getTimestamp();
        $this->tagNameB = "TagB_" . $time->getTimestamp();
        $this->tagNameAEntityOne = 1;
        $this->tagNameAEntityTwo = 2;
    }

    //==================================================================================================================
    // Helper Methods
    //==================================================================================================================
    private function WaitForClockTick()
    {
        sleep(1);
    }
    
    //==================================================================================================================
    // Tests
    //==================================================================================================================
    
    //-----------------------------
    // Tests - tags
    //-----------------------------    
    /** @test */
    public function StandardTagShouldReturnTheSameValueIfIncrementHasNotBeenCalled()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $version1 = $tag->getFullTag();

        $this->WaitForClockTick();

        $tag2 = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $version2 = $tag2->getFullTag();

        $this->assertEquals($version1, $version2, "The second call to getVersion did not return the expected value");
    }

    /** @test */    
    public function StandardTagShouldReturnDifferentValuesIfIncrementHasBeenCalled()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $version1 = $tag->getFullTag();
        $tag->Increment();

        $tag2 = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $version2 = $tag2->getFullTag();

        $this->assertNotEquals($version1, $version2, "The second call to getVersion should not have matched the previous value");
    }

    /** @test */
    public function StandardTagsShouldReturnDifferentValuesForDifferentEntities()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);

        $tag2 = new Tag\Standard($this->tagNameA, $this->tagNameAEntityTwo);

        $this->assertNotEquals($tag->getFullTag(), $tag2->getFullTag(), "key2 and key1 should have had different tag values");
    }

    /** @test */
    public function StandardTagsShouldReturnDifferentValuesForDifferentTags()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);

        $tag2 = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne);

        $this->assertNotEquals($tag->getFullTag(), $tag2->getFullTag(), "key2 and key1 should have had different tag values");
    }

    /** @test */
    public function CallingIncrementVersionShouldChangeTheVersionOnStandardTagInstances()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $version = $tag->getTagVersion();
        $tag->Increment();
        $version2 = $tag->getTagVersion();

        $this->assertNotEquals($version,$version2);
    }

    /** @test */
    public function IncrementingOneTagGroupInstanceShouldNotChangeTheVersionOfOtherTAgInstancePairs()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $tag2 = new Tag\Standard($this->tagNameA, $this->tagNameAEntityTwo);
        $version = $tag->getFullTag();
        $tag2->Increment();
        $this->assertEquals($version, $tag->getFullTag());
    }

    /** @test */
    public function IncrementingOneTagEntityShouldNotChangeVersionOfDifferentTagForTheSameIdValue()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $tag2 = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne);
        $version = $tag->getFullTag();
        $tag2->Increment();
        $this->assertEquals($version, $tag->getFullTag());
    }

    //-----------------------------
    // Tests - keys
    //-----------------------------        
    /** @test */
    public function GetKeyShouldCorrectlyPullVersionsFromAllTags()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $tag2 = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne);
        $theKey = new Key\Standard("ThisIsAKey", array($tag,$tag2));

        $expected = md5("ThisIsAKey_:t" . $tag->getFullTag() . ":t" . $tag2->getFullTag());
        $this->assertEquals($expected, $theKey->getKey());
    }

    /** @test */
    public function GetKeyShouldWorkWithASingleTag()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $theKey = new Key\Standard("ThisIsAKey", array($tag));

        $expected = md5("ThisIsAKey_:t" . $tag->getFullTag());
        $this->assertEquals($expected, $theKey->getKey());
    }

    /** @test */
    public function GetKeyShouldReturnSameValueOnSubsequentCallsIfTagsAreNotIncremented()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $tag2 = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne);
        $theKey = new Key\Standard("ThisIsAKey",  array($tag,$tag2));

        $firstKey = $theKey->getKey(false);

        $this->WaitForClockTick();

        $theKey = new Key\Standard("ThisIsAKey", array($tag,$tag2));
        $secondKey = $theKey->getKey(false);

        $this->assertEquals($firstKey, $secondKey);
    }

    /** @test */
    public function GetKeyShouldReturnDifferentValuesIfTagsAreIncremented()
    {
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $tag2 = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne);
        $theKey = new Key\Standard("ThisIsAKey",  array($tag,$tag2));
        $firstKey = $theKey->getKey(false);
        $tag2b = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne);
        $tag2b->Increment();
        $theKey = new Key\Standard("ThisIsAKey",  array($tag,$tag2));
        $secondKey = $theKey->getKey(false);
        $this->assertNotEquals($firstKey, $secondKey);
    }

    /** @test */
    public function GetKeyShouldBeAbleToProcessGroupsOfTagsWithDifferentCacheHandlersAndReturnTheSameKeyIfNotIncremented()
    {
        if(is_null($this->apcHandler)) {
            $this->markTestSkipped('Command Line APC must be enabled to execute this test');
        }
        
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne, null, $this->apcHandler);
        $tag2 = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne);
        $theKey = new Key\Standard("ThisIsAKey",  array($tag,$tag2));

        $firstKey = $theKey->getKey(false);

        $this->WaitForClockTick();
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne, null, $this->apcHandler);
        $theKey = new Key\Standard("ThisIsAKey", array($tag,$tag2));
        $secondKey = $theKey->getKey(false);

        $this->assertEquals($firstKey, $secondKey);       
    }
    
    /** @test */
    public function GetKeyShouldBeAbleToProcessGroupsOfTagsWithDifferentCacheHandlersAndReturnADifferentKeyIfIncremented()
    {
        if(is_null($this->apcHandler)) {
            $this->markTestSkipped('Command Line APC must be enabled to execute this test');
        }

        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $tag2 = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne, null, $this->apcHandler);
        $theKey = new Key\Standard("ThisIsAKey",  array($tag,$tag2));
        $firstKey = $theKey->getKey(false);
        $tag2b = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne, null, $this->apcHandler);
        $tag2b->Increment();
        $tag2b->Increment();
        $theKey = new Key\Standard("ThisIsAKey",  array($tag,$tag2));
        $secondKey = $theKey->getKey(false);
        $this->assertNotEquals($firstKey, $secondKey);
    }
    
    /** @test */
    public function AKeyShouldRemainConstantWhenUsingOnlyStaticTags()
    {
        $tag = new Tag\Constant($this->tagNameA, $this->tagNameAEntityOne,5);
        $tag2 = new Tag\Constant($this->tagNameB, $this->tagNameAEntityOne,8);
        $theKey = new Key\Standard("ThisIsAKey",  array($tag,$tag2));

        $firstKey = $theKey->getKey(false);
        $tag->increment();
        $tag2->increment();
        $theKey = new Key\Standard("ThisIsAKey", array($tag,$tag2));
        $secondKey = $theKey->getKey(false);
        $this->assertEquals($firstKey, $secondKey);
    }
    
    
    //-----------------------------
    // Tests - key rings
    //-----------------------------        
    /** @test */
    public function KeyDefinedUsingKeyRingShouldMatchEquivelentManuallyConstructedKey()
    {
        // Define Key
        
        $cacheHandlers = array(
            'memcache' => new \NoizuLabs\FragmentedKeys\CacheHandler\Memcached($this->container['memcache']),
            'memory' => new \NoizuLabs\FragmentedKeys\CacheHandler\Memory()
            );
        $globalOptions = array(
          'type' => 'standard'  
        );
        $tagOptions = array(
            $this->tagNameB => array('type' => 'constant', 'version' => 5)
        );
        
        $ring = new FragmentedKeys\KeyRing($globalOptions,  $tagOptions, 'memcache', $cacheHandlers);
        $ring->DefineKey("Users", array($this->tagNameA, array('tag' => $this->tagNameB , 'cacheHandler' => 'memory', 'version' => null, 'type'=>'standard'), $this->tagNameB));
                
        $tag = new Tag\Standard($this->tagNameA, $this->tagNameAEntityOne);
        $tag2 = new Tag\Standard($this->tagNameB, $this->tagNameAEntityOne, null, new \NoizuLabs\FragmentedKeys\CacheHandler\Memory());
        $tag3 = new Tag\Constant($this->tagNameB, $this->tagNameAEntityTwo, 5);
        $key1 = new Key\Standard("Users", array($tag,$tag2,$tag3));
        $firstKey = $key1->getKey(false); 
        
        $key2 = $ring->getUsersKeyObj($this->tagNameAEntityOne, $this->tagNameAEntityOne, $this->tagNameAEntityTwo);
        $secondKey = $key2->getKey(false); 
        $this->assertEquals($firstKey, $secondKey);
       
    }
}