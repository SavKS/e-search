<?php

namespace Savks\ESearch\Support;

use Closure;

use Savks\ESearch\Resources\Config\{
    ScopesRepository,
    SortsRepository
};

class Config
{
    /**
     * @var SortsRepository
     */
    public readonly SortsRepository $sorts;

    /**
     * @var ScopesRepository
     */
    public readonly ScopesRepository $scopes;

    /**
     * Config constructor.
     */
    public function __construct()
    {
        $this->sorts = new SortsRepository();
        $this->scopes = new ScopesRepository();
    }

    /**
     * @param Closure $handler
     * @return static
     */
    public function defineSorts(Closure $handler): static
    {
        $handler($this->sorts);

        return $this;
    }

    /**
     * @param Closure $handler
     * @return static
     */
    public function defineQueryScopes(Closure $handler): static
    {
        $handler($this->scopes);

        return $this;
    }
}
