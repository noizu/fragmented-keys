<?php
namespace NoizuLabs\FragmentedKeys;
use NoizuLabs\FragmentedKeys as FragmentedKeys;


/**
 * This is the Standard Implementation of a KeyGroup.
 *    Additional Implementations are:
 * - Database or File Backed KeyGroups - Commonly you will want to persist versioning information on a layer that
 *   is less volatile than memcache. Memcache will perform garbage collection and could remove a keygroup that underpins a large set of data.
 *   If this keygroup is not persisted to a database or file persistence layer this garbage collection could result in a unnecessarily large swatch of
 *   cache in-validations.
 * - The Constant KeyGroup (A keygroup where the version number is static)
 * - The Time Delayed KeyGroup (a keygroup that uses a time delay when incrementing keygroup version. For example you may only want data up-to-date within
 * 10-30  minutes for some uses and within 1-2 minutes for other uses. A time delayed key group tracks multiple versions itself set to expire at different time intervals.).
 * allowing the end user to specify how stale of data they are willing to work with.
 */
class Tag implements FragmentedKeys\ITag
{
    /**
     * tag group 
     * (e.g. user, apple, etc.)
     * @var string
     */
    protected $tagName;
    
    /**
     * instance/index within group 
     * e.g. user:Keith
     * @var mixed
     */
    protected $tagInstance;
    
    /**
     * tag-instance version. 
     * E.g. user:keith:version5
     * @var int
     */
    protected $version;    
    protected $cachePrefix;
    
    /**
     * cache handler
     * @var \NoizuLabs\FragmentedKeys\ICacheHandler 
     */
    protected $cacheHandler;
    static protected $VERSION_SEPERATOR = ":v";
    static protected $INDEX_SEPERATOR = "_";
       
    /**
     * @param string $tag - name of tag/group ("user", "day_of_week", "username", etc)
     * @param string $instance - Tag instance (unique id of group record in question)
     * @param int $version - optional group version can be manually set if desired.
     * @param ICacheHandler $handler - optional cache handler override
     * @param string $prefix - optional  prefix override
     */
    public function __construct($tag, $instance = "na", $version = null, $handler = null, $prefix = null)
    {
        global $container;
        $this->tagName = $tag;
        $this->tagInstance = $instance;
        if(!empty($version)) {
            $this->version = $version;
        }
        if(!empty($handler)) {
            if ( is_a($handler, "\NoizuLabs\FragmentedKeys\ICacheHandler")) {
                $this->cacheHandler = $handler;                
            } else {
                trigger_error("\$handler param of Tag constructor must be null or of type ICacheHandler");
            }
        } else {
            $this->cacheHandler = \NoizuLabs\FragmentedKeys\Configuration::getDefaultCacheHandler();    
        }                
        
        if(!empty($prefix)) {
            $this->cachePrefix = $prefix;
        } else {
            $this->cachePrefix = \NoizuLabs\FragmentedKeys\Configuration::getGlobalPrefix();
        }
    }
    
    /**
     * This Control function is used to determine if the fragmentedKey that contains this keygroup may multiget memcache for its version value.
     * For items like Static Keys we set this value to false to reduce strain on the memcache server albeit at increased php processing time to check this value.
     */
    public function DelegateMemcacheQuery($group) {
        if($group == $this->cacheHandler->getGroupName()) {
            return true;
        } else {           
            return false;
        }
    }

    public function getCacheHandler() {
        return $this->cacheHandler;
    }
    
    public function setCacheHandler(\NoizuLabs\FragmentedKeys\ICacheHandler $handler) {
        $this->cacheHandler = $handler;
    }

    /**
     * returns the current version value for this group.
     * @return int
     */
    public function getTagVersion()
    {
        return !empty($this->version) ? $this->version : $this->_getVersion();
    }

    /**
     * Modify the version used for this group.
     * @param int $version
     * @param bool $update  - Update Memcache/Persistant store.
     */
    public function setTagVersion($version, $update)
    {
        $this->version = $version;
        if($update) {
            $this->_StoreVersion();
        }
    }

    /**
     * Retrieve this groups tag (Unique GroupName Plus Versioning Info );
     * @return
     */
    public function getFullTag() {
        return $this->getTagName() . self::$VERSION_SEPERATOR . $this->getTagVersion();
    }

    /**
     * Retrive this groups name. (For example  USER_123423);
     * @return
     */
    public function getTagName()
    {
        return $this->tagName . self::$INDEX_SEPERATOR . $this->tagInstance . $this->cachePrefix;
    }

    /**
     * Increment version number.
     */
    public function Increment()
    {
        if($this->version == null) {
            $this->_getVersion();
        }
        $this->version += .1;
        $this->_StoreVersion();
    }

    /**
     * Reset version number. We use a microtime stamp to insure this value is
     * always unique and will not result in a pull of invalidated data.
     */
    public function ResetTagVersion()
    {
        $this->version = microtime(true) * 1000;
        $this->_StoreVersion();
    }

    /**
     * return current version number of tag-instance
     * @return int
     */
    protected function _getVersion()
    {
        if(empty($this->version))
        {
            $result = $this->cacheHandler->get($this->getTagName());
            if(empty($result)) {
                $this->ResetTagVersion();
            } else {
                $this->version = $result;
            }
        }
        return $this->version;
    }

    /**
     * update version in cache.
     */
    protected function _StoreVersion()
    {
        $this->cacheHandler->set($this->getTagName(),$this->version);
    }
}
