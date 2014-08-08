<?php
namespace NoizuLabs\FragmentedKeys\Tag;
use NoizuLabs\FragmentedKeys as FragmentedKeys;


/**
 * This is the Constant Implementation of a KeyGroup. It's version never changes.
 */
class Constant extends FragmentedKeys\Tag
{

        
    /**
     *
     * @param string $group - name of group ("user", "day_of_week", "username", etc)
     * @param string $index - index  for group (unique id of group record in question)
     * @param int $version - group version can be manually set if desired.
     */
    public function __construct($group, $index = "na", $version = null)
    {
        global $container;
        $this->groupName = $group;
        $this->groupIndex = $index;
        if(!empty($version)) {
            $this->version = $version;
        } else {
            $this->version = 1; 
        }
        $this->cacheHandler = new \NoizuLabs\FragmentedKeys\CacheHandler\Memory();
        $this->cachePrefix = \NoizuLabs\FragmentedKeys\Configuration::getGlobalPrefix();
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
        return $this->groupName . self::$INDEX_SEPERATOR . $this->groupIndex . $this->cachePrefix;
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