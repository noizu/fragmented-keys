<?php
namespace NoizuLabs\FragmentedKeys;

/**
 * The Key Group is the basic tag + version_info node which our fragmented
 * key system relies on.
 */
interface ITag
{
    /**
     * Instantiate Tag. 
     */
    public function __construct($group, $index, $version);
    
    /**
     * get current Tag-Instance Version. 
     */
    public function getTagVersion();
    
    /**
     * get cache handler; 
     */
    public function getCacheHandler();
    
    /**
     * Set Tag-Instance Version to specific value. 
     */
    public function setTagVersion($version,$update);
    
    /**
     * Reset version associated with Tag-Instance
     */
    public function ResetTagVersion();

    /**
     * Tag Name, Instance & Version
     */
    public function getFullTag();
    
    /*
     * get Tag-Instance
     */
    public function getTagName();

    /**
     * Increment Tag-Instance Version.
     */
    public function Increment();

    /**
     * Allow Upstream Multi-Get for Performance Reasons
     */
    public function DelegateMemcacheQuery($group);
}
