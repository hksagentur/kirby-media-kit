<?php

namespace Hks\MediaKit\Cms\Contracts;

use Hks\MediaKit\Cms\Image;
use Hks\MediaKit\Cms\Video;

interface HasKeyVisual
{
    public function keyVisual(): Image|Video|null;
}
