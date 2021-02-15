<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function path()
    {
        return '/links/'.$this->id;
    }

    public function linkspath()
    {
        return '/links';
    }

    public function baseUrl()
    {
        return URL::to('/');
    }

    public function owner()
    {
        return $this->belongsTo(User::class);
    }
}
