<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class ShortLink extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'link'
    ];

    /**
     * Returns the current entry path.
     *
     * @return string
     */
    public function path(): string
    {
        return '/links/'.$this->id;
    }

    /**
     * Returns the base path.
     *
     * @return string
     */
    public function baseUrl(): string
    {
        return URL::to('/');
    }

    /**
     * Checks if actual user is owner of entry.
     *
     * @return BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
