<?php
namespace NoizuLabs\FragmentedKeys\Tag;
use NoizuLabs\FragmentedKeys as FragmentedKeys;


/**
 * This is the Standard Implementation of a KeyGroup. Versions are updated as soon as they are invalidated.
 */
class Standard extends FragmentedKeys\Tag
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
        parent::__construct($tag, $instance, $version, $handler, $prefix);
    }
}