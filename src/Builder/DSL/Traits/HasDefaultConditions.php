<?php

namespace Savks\ESearch\Builder\DSL\Traits;

trait HasDefaultConditions
{
    use HasBool;
    use HasExists;
    use HasMatchNone;
    use HasNested;
    use HasRange;
    use HasRaw;
    use HasTerm;
    use HasTerms;
}
