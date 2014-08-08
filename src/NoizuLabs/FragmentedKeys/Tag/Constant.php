<?php
namespace NoizuLabs\FragmentedKeys\Tag;
use NoizuLabs\FragmentedKeys as FragmentedKeys;


/**
 * This is the Constant Implementation of a KeyGroup. It's version never changes.
 */
class Constant extends FragmentedKeys\Tag
{       
    /**
     * @param string $tag - name of tag/group ("user", "day_of_week", "username", etc)
     * @param string $instance - Tag instance (unique id of group record in question)
     * @param int $version - optional group version can be manually set if desired.
     * @param ICacheHandler $handler - optional cache handler override
     * @param string $prefix - optional  prefix override
     */
    public function __construct($tag, $instance = "na", $version = null, $handler = null, $prefix = null)
    {
        if(is_null($version)) {
            $version = 1;
        }        
        parent::__construct($tag, $instance, $version, $handler, $prefix); 
    }
    
    /**
     * Because we are a constant Tag we never need to have our version updated.. 
     * @param string $group
     * @return boolean
     */
    public function DelegateMemcacheQuery($group) {
        return false; 
    }

    /**
     * returns the current version value for this group.
     * @return int
     */
    public function getTagVersion()
    {
        return $this->version; 
    }

    /**
     * Modify the version used for this group (Does nothing against constant tags)
     * @param int $version
     * @param bool $update  - Update Memcache/Persistant store.
     */
    public function setTagVersion($version, $update = false)
    {        
        /* Do Nothing, Constant Tag*/
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
        /*Do Nothing, Constant Tag*/
    }

    /**
     * Reset version number. We use a microtime stamp to insure this value is
     * always unique and will not result in a pull of invalidated data.
     */
    public function ResetTagVersion()
    {
        /*Do Nothing, Constant Tag*/
    }

    /**
     * return current version number of tag-instance
     * @return int
     */
    protected function _getVersion()
    {
        return $this->version;
    }

    /**
     * update version in cache.
     */
    protected function _StoreVersion()
    {
        /*Do Nothing, Constant Tag*/
    }                
}