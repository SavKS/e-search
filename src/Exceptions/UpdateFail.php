<?php

namespace Savks\ESearch\Exceptions;

use Savks\ESearch\Support\MutableResource;
use Savks\ESearch\Updates\Update;

class UpdateFail extends BaseException
{
    /**
     * @param string $message
     * @param MutableResource $resource
     * @param Update $update
     */
    public function __construct(string $message, MutableResource $resource, Update $update)
    {
        $message = \sprintf(
            '[Can\'t apply update][%s][%s] For resource "%s". Error: %s',
            $update::type(),
            $resource::name(),
            $update->name(),
            $message
        );

        parent::__construct($message);
    }
}
