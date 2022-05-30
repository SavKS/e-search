<?php

namespace Savks\ESearch\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $connection_name
 * @property string $resource
 * @property string $type
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ESearchUpdate extends Model
{
    /**
     * @var string[]
     */
    protected $guarded = ['id'];
}
