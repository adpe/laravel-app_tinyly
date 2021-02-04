<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function path()
    {
        return '/links/';
    }

    public function owner()
    {
        return $this->belongsTo(User::class);
    }
}
