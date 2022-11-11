<?php

namespace Savks\ESearch\Support;

use Closure;

use Savks\ESearch\Resources\Config\{
    ScopesRepository,
    SortsRepository
};

class Config
{
    public readonly SortsRepository $sorts;

    public readonly ScopesRepository $scopes;

    public function __construct()
    {
        $this->sorts = new SortsRepository();
        $this->scopes = new ScopesRepository();
    }

    public function defineSorts(Closure $handler): static
    {
        $handler($this->sorts);

        return $this;
    }

    public function defineQueryScopes(Closure $handler): static
    {
        $handler($this->scopes);

        return $this;
    }
}
