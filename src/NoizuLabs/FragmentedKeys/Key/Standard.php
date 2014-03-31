<?php
namespace NoizuLabs\FragmentedKeys\Key;

/**
*  This is a standard implementation of a fragmented key, One obvious extensions would deal with the nesting of keys within keys.
*/
class Standard {
    protected $key;
    protected $groupId;
    protected $memcache;
    protected $keyGroups = array();
    static protected $TAG_SEPERATOR = ":t";
    static protected $INDEX_SEPERATOR = "_";

    public function __construct($key, array $keyGroups = array(), $groupId = "") {
        global $container;
        $this->key = $key;
        $this->groupId = $groupId;

        foreach($keyGroups as $keyGroup)
        {
            $this->keyGroups[$keyGroup->getTagName()] = $keyGroup;
        }
        $this->memcache = $container['memcache'];
    }

    public function AddKeyGroup(ITag $keyGroup) {
        $this->keyGroups[$keyGroup->getGroupTag()] = $keyGroup;
    }

    public function getKey($hash = true)
    {
        $key =  $this->key . self::$INDEX_SEPERATOR . $this->groupId . self::$TAG_SEPERATOR . implode(self::$TAG_SEPERATOR, $this->gatherTags());
        if($hash) {
             $key = md5($key);
        }
        return $key;
    }

    /**
     *  While it would be architecturally cleaner to gather group versions from a KeyGroup function call
     *  the use of memcache multiget helps us avoid some bottle-necking produced by the increased number of key-versions we
     *  need to look-up when using this fragmented key system.
     */
    protected function GatherGroupVersions() {
        $group_tags = array_keys($this->keyGroups);

        foreach ($group_tags as $group_tag) {
            if($this->keyGroups[$group_tag]->DelegateMemcacheQuery() == false) {
                unset($group_tag);
            }
        }

        $tags = $this->memcache->getMulti($group_tags);
        foreach($this->keyGroups as $key => &$group) {
            if(array_key_exists($key, $tags))
            {
                $group->setTagVersion($tags[$key],false);
            } else {
                $group->ResetTagVersion();
            }
        }
    }

    /**
     *
     * @return array tag strings of all tags included within this key.
     */
    protected function GatherTags()
    {
        $this->GatherGroupVersions();
        $tags = array();
        foreach($this->keyGroups as $group)
        {
            $tags[] = $group->getFullTag();
        }
        return $tags;
    }
}
