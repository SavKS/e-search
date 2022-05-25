<?php

namespace Savks\ESearch\Support;

enum RequestTypes: string
{
    case SAVE = 'save';
    case BULK_SAVE = 'bulk-save';
    case DELETE = 'delete';
    case BULK_DELETE = 'bulk-delete';
    case TRUNCATE = 'truncate';
    case DELETE_BY_QUERY = 'delete-by-query';
}
