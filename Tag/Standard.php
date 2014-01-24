<?php
namespace NoizuLabs\FragmentedKeys\Tag;
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
class Standard implements FragmentedKeys\ITag
{
    protected $groupName;
    protected $groupIndex;
    protected $version;
    protected $memcache;
    static protected $VERSION_SEPERATOR = ":v";
    static protected $INDEX_SEPERATOR = "_";

    /**
     * This Control function is used to determine if the fragmentedkey that contains this keygroup may multiget memcache for its version value.
     * For items like Static Keys we set this value to false to reduce strain on the memcache server albeit at increased php processing time to check this value.
     */
    public function DelegateMemcacheQuery() {
        return true;
    }

    /**
     *
     * @param string $group - name of group ("user", "day_of_week", "username", etc)
     * @param string $index - index  for group (unique id of group record in question)
     * @param int $version - group version can be manually set if desired.
     * @param bool $update - group version will not actually be updated in memcache unless specified.
     */
    public function __construct($group, $index = "na", $version = null, $update = false)
    {
        global $container;
        $this->groupName = $group;
        $this->groupIndex = $index;
        if(!empty($version)) {
            $this->version = $version;
            if($update){
                $this->_StoreVersion();
            }
        }
        $this->memcache = $container['memcache'];
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
        return $this->groupName . self::$INDEX_SEPERATOR . $this->groupIndex;
    }

    /**
     * Increment version number.
     */
    public function Increment()
    {
        if($this->version == null) {
            $this->_getVersion();
        }
        $this->version += 1;
        $this->_StoreVersion();
    }

    /**
     * Reset version number. We use a microtime stamp to insure this value is
     * always unique and will not result in a pull of invalidated data.
     */
    public function ResetTagVersion()
    {
        $this->version = intval(microtime(true) * 1000);
        $this->_StoreVersion();
    }

    protected function _getVersion()
    {
        if(empty($this->version))
        {
            $result = $this->memcache->get($this->getTagName());
            if(empty($result)) {
                $this->ResetTagVersion();
            } else {
                $this->version = $result;
            }
        }
        return $this->version;
    }

    protected function _StoreVersion()
    {
        $this->memcache->set($this->getTagName(),$this->version);
    }
}
