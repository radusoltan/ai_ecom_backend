<?php

declare(strict_types=1);

namespace App\Shared\Http\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class SkipEnvelope
{
}
