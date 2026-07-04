<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Key;

use NoizuLabs\FragmentedKeys\KeyInterface;
use NoizuLabs\FragmentedKeys\TagInterface;

class StandardKey implements KeyInterface
{
    private const string TAG_SEPARATOR = ':t';
    private const string INDEX_SEPARATOR = '_';

    /** @var list<TagInterface> */
    private array $tags;

    /**
     * @param list<TagInterface> $tags
     */
    public function __construct(
        private readonly string $key,
        array $tags = [],
        private readonly string $groupId = '',
    ) {
        $this->tags = $tags;
    }

    public function addTag(TagInterface $tag): void
    {
        $this->tags[] = $tag;
    }

    public function getKeyStr(bool $hash = true): string
    {
        $this->gatherGroupVersions();
        $raw = $this->key . self::INDEX_SEPARATOR . $this->groupId;
        foreach ($this->tags as $tag) {
            $raw .= self::TAG_SEPARATOR . $tag->getFullTag();
        }

        if ($hash) {
            return md5($raw);
        }
        return $raw;
    }

    public function __toString(): string
    {
        return $this->getKeyStr(false);
    }

    private function gatherGroupVersions(): void
    {
        /** @var array<string, array{handler: \NoizuLabs\FragmentedKeys\CacheHandlerInterface, tags: array<string, TagInterface>}> $groups */
        $groups = [];

        foreach ($this->tags as $tag) {
            $handler = $tag->getCacheHandler();
            $group = $handler->groupName();
            if (!$tag->delegateCacheQuery($group)) {
                continue;
            }
            if (!isset($groups[$group])) {
                $groups[$group] = ['handler' => $handler, 'tags' => []];
            }
            $groups[$group]['tags'][$tag->getTagName()] = $tag;
        }

        foreach ($groups as $groupData) {
            $tagNames = array_keys($groupData['tags']);
            $versions = $groupData['handler']->getMulti($tagNames);
            foreach ($groupData['tags'] as $tagName => $tag) {
                if (isset($versions[$tagName])) {
                    $tag->setTagVersion((int) $versions[$tagName]);
                } else {
                    $tag->resetTagVersion();
                }
            }
        }
    }
}
