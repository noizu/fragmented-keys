<?php
namespace NoizuLabs\FragmentedKeys;

/**
 * The various key groups are merged together to form our final cache key.
 */
interface IKeyRing
{
    public function __construct(array $globalOptions, array $globalTagOptions, $defaultCacheHandler = "default", array $cacheHandlers = array(), $defaultPrefix = null);
    public function setTagOptions($tag, array $options) ;
    public function setGlobalOptions(array $options);
    public function getTagOptions($tag, $options);
    public function getGlobalOptions();
    public function DefineKey($key, array $params, array $globals = array());
}