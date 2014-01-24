<?php
namespace NoizuLabs\FragmentedKeys;

/**
 * The Key Group is the basic tag + version_info node which our fragmented
 * key system relies on.
 */
interface ITag
{
    public function __construct($group, $index, $version);
    public function getTagVersion();
    public function setTagVersion($version,$update);
    public function ResetTagVersion();

    public function getFullTag();
    public function getTagName();

    public function Increment();

    public function DelegateMemcacheQuery();
}
