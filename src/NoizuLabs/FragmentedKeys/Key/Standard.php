<?php
namespace NoizuLabs\FragmentedKeys\Key;

/**
*  This is a standard implementation of a fragmented key. 
*/
class Standard implements \NoizuLabs\FragmentedKeys\IKey{
    protected $key;
    protected $groupId;
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
    }

    public function AddKeyGroup(\NoizuLabs\FragmentedKeys\ITag $tag) {
        $this->keyGroups[$keyGroup->getGroupTag()] = $tag;
    }

    /**
     * @deprecated
     * @param bool $hash
     * @return string
     */
    public function getKey($hash = true)
    {
        return $this->getKeyStr($hash); 
    }

    /**
     * calculate composite key
     * @param bool $hash use true to return md5 (memcache friendly) key or use false to return raw key for visual inspection. 
     * @return string
     */
    public function getKeyStr($hash = true)
    {
        $key =  $this->key . self::$INDEX_SEPERATOR . $this->groupId . self::$TAG_SEPERATOR . implode(self::$TAG_SEPERATOR, $this->gatherTags());
        if($hash) {
             $key = md5($key);
        }
        return $key;
    }
    
    /**
     *  Bulk Fetch tag-instance versions. 
     *  While it would be architecturally cleaner to gather group versions from a KeyGroup function call
     *  the use of memcache/apc multiget helps us avoid some bottle-necking produced by the increased number of key-versions we
     *  need to look-up when using this fragmented key system.
     */
    protected function GatherGroupVersions() {
        $tags = array_keys($this->keyGroups);

        $handlers = array();
        foreach($tags as $tag) {
            $handler = $this->keyGroups[$tag]->getCacheHandler()->getGroupName();
            if(!array_key_exists($handler, $handlers)) {
                $handlers[$handler] = array();
            }
            $handlers[$handler][] = $tag;
        }
        
        $tags = array();
        
        foreach($handlers as $handler => $group_tags) {
            foreach ($group_tags as $group_tag) {
                if($this->keyGroups[$group_tag]->DelegateMemcacheQuery($handler) == false) {
                    unset($group_tag);
                }
            }
            
            if(!empty($group_tags)) {                
                $cacheHandler = $this->keyGroups[$group_tags[0]]->getCacheHandler();
                $r = $cacheHandler->getMulti($group_tags);
                if($r) {
                    $tags = array_merge($tags, $r);
                }
            }
        }
        
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
    
    public function __toString() {
        return $this->getKey(false);
    }
}
