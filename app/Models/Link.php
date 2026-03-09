<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * Class Link
 *
 * @mixin Builder
 * @package App
 */
class Link extends Model
{
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'active_period_start_at' => 'datetime',
        'active_period_end_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'targets' => 'object',
        'qr_styles'    => 'array',
        'qr_generated' => 'boolean',
        'data' => 'array',
    ];

    /**
     * Get the total clicks count under the Link.
     *
     * @return int
     */
    public function getTotalClicksAttribute()
    {
        return $this->hasMany('App\Models\Stat')->where('link_id', $this->id)->count();
    }

    /**
     * Get the space of the link.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function space()
    {
        return $this->belongsTo('App\Models\Space');
    }

    /**
     * Get the domain of the link.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function domain()
    {
        return $this->belongsTo('App\Models\Domain');
    }

    /**
     * Get the stats of the link.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stats()
    {
        return $this->hasMany('App\Models\Stat')->where('link_id', $this->id);
    }

    /**
     * Get the user that owns the link.
     *
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }

    /**
     * Get the pixels of the link.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function pixels() {
        return $this->belongsToMany('App\Models\Pixel');
    }

    /**
     * @param Builder $query
     * @param $value
     * @return Builder
     */
    public function scopeSearchTitle(Builder $query, $value)
    {
        return $query->where('title', 'like', '%' . $value . '%');
    }

    /**
     * @param Builder $query
     * @param $value
     * @return Builder
     */
    public function scopeSearchUrl(Builder $query, $value)
    {
        return $query->where('url', 'like', '%' . $value . '%');
    }

    /**
     * @param Builder $query
     * @param $value
     * @return Builder
     */
    public function scopeSearchAlias(Builder $query, $value)
    {
        return $query->where('alias', 'like', '%' . $value . '%');
    }

    /**
     * @param Builder $query
     * @param $value
     * @return Builder
     */
    public function scopeOfUser(Builder $query, $value)
    {
        return $query->where('user_id', '=', $value);
    }

    /**
     * @param Builder $query
     * @param $value
     * @return Builder
     */
    public function scopeOfSpace(Builder $query, $value)
    {
        return $query->where('space_id', '=', $value)
            ->when(!$value, function ($query) use ($value) {
                $query->orWhereNull('space_id');
            });
    }

    /**
     * @param Builder $query
     * @param $value
     * @return Builder
     */
    public function scopeOfDomain(Builder $query, $value)
    {
        return $query->where('domain_id', '=', $value);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeExpired(Builder $query)
    {
        return $query->where(function ($query) {
            $query->whereNotNull(['active_period_start_at', 'active_period_end_at'])
                ->where([['active_period_start_at', '>=', Carbon::now()->startOfMinute()], ['active_period_end_at', '<=', now()]]);
            })->orWhere(function ($query) {
                $query->where('clicks_limit', '>', 0)
                    ->whereColumn('clicks', '>=', 'clicks_limit');
            });
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->where(function ($query) {
                $query->where(function ($nestedQuery) {
                    $nestedQuery->whereNull(['active_period_start_at', 'active_period_end_at']);
                })
                ->orWhere(function ($query) {
                    $query->where([['active_period_start_at', '>', now()], ['active_period_end_at', '>', Carbon::now()->startOfMinute()]])
                        ->orWhere([['active_period_start_at', '<', now()], ['active_period_end_at', '<', Carbon::now()->startOfMinute()]]);
                });
            })
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('clicks_limit', '=', null)
                        ->orWhere('clicks_limit', '=', 0);
                })
                ->orWhere(function ($query) {
                    $query->where('clicks_limit', '>', 0)
                        ->whereColumn('clicks', '<', 'clicks_limit');
                });
            });
    }

    /**
     * Encrypt the link's redirect password.
     *
     * @param $value
     */
    public function setRedirectPasswordAttribute($value)
    {
        $this->attributes['redirect_password'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt the link's redirect password.
     *
     * @param $value
     * @return string
     */
    public function getRedirectPasswordAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Encrypt the link's stats password.
     *
     * @param $value
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt the link's stats page password.
     *
     * @param $value
     * @return string
     */
    public function getPasswordAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the display URL.
     *
     * @return string
     */
    public function getDisplayUrlAttribute($value)
    {
        return str_replace(['http://', 'https://'], '', $this->url);
    }

    /**
     * Get the display short URL.
     *
     * @return string
     */
    public function getDisplayShortUrlAttribute()
    {
        return str_replace(['http://', 'https://'], '', $this->shortUrl);
    }

    /**
     * Get the short URL.
     *
     * @return string
     */
    public function getShortUrlAttribute()
    {
        return (isset($this->domain) ? (parse_url($this->domain->url, PHP_URL_HOST) == parse_url(config('app.url'), PHP_URL_HOST) ? config('app.url') : $this->domain->url) : config('app.url')) . '/' . $this->alias;
    }
}
