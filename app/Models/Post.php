<?php

namespace App\Models;

use App\Models\User;
use App\Models\Favorite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'body', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function favoritedBy(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
}
