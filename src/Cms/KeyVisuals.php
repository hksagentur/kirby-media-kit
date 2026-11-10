<?php

namespace Hks\MediaKit\Cms;

use Kirby\Toolkit\A;
use Kirby\Toolkit\Collection;

/**
 * @extends Collection<Image|Video>
 */
class KeyVisuals extends Collection
{
    public function toPreloadLinks(): string
    {
        return A::join(array_filter(
            $this->toArray(
                fn (Image|Video $keyVisual) => $keyVisual->toPreloadLink(),
            )
        ), "\n");
    }
}
