<?php

namespace Hks\MediaKit\Cms\Concerns;

use Hks\MediaKit\Cms\Contracts\HasKeyVisual as HasKeyVisualContract;
use Hks\MediaKit\Cms\Image;
use Hks\MediaKit\Cms\KeyVisuals;
use Hks\MediaKit\Cms\Video;
use Kirby\Cms\Blocks;

trait HasKeyVisual
{
    public function keyVisuals(?string $languageCode = null): KeyVisuals
    {
        $keyVisuals = new KeyVisuals();

        foreach ($this->blocks($languageCode) as $block) {
            if (! $block instanceof HasKeyVisualContract) {
                continue;
            }

            $keyVisual = $block->keyVisual();

            if ($keyVisual?->hasPriority()) {
                $keyVisuals->append($keyVisual);
            }
        }

        return $keyVisuals;
    }

    public function keyVisual(?string $languageCode = null): Image|Video|null
    {
        return $this->keyVisuals($languageCode)->first();
    }

    abstract public function blocks(?string $languageCode = null): Blocks;
}
