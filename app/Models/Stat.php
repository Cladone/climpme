<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Stat
 *
 * @mixin Builder
 * @package App
 */
class Stat extends Model
{
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the link that owns the stat.
     */
    public function link()
    {
        return $this->belongsTo('App\Models\Link');
    }

    /**
     * @param Builder $query
     * @param $value
     * @return Builder
     */
    public function scopeSearch(Builder $query, $value, $column)
    {
        return $query->where($column, 'like', '%' . $value . '%');
    }
}
