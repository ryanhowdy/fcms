<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscussionComment extends Model
{
    use HasFactory;

    /**
     * Get the discussion this comment belongs to
     */
    public function discussion()
    {
        return $this->belongsTo(Discussion::class);
    }
}
