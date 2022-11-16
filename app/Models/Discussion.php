<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discussion extends Model
{
    use HasFactory;

    public function comment()
    {
        return $this->hasOne(DiscussionComment::class)->oldestOfMany();
    }

    /**
     * Get the comments for the discussion
     */
    public function comments()
    {
        return $this->hasMany(DiscussionComment::class);
    }
}
