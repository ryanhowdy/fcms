<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NavigationLink extends Model
{
    use HasFactory;

    public function getIconAttribute()
    {
        if (empty($this->route_name))
        {
            return null;
        }

        $routeToIconLkup = [
            'home'              => 'house',
            'calendar'          => 'calendar-date',
            'members'           => 'person',
            'addresses'         => 'person-rolodex',
            'discussions'       => 'chat-text',
            'photos'            => 'images',
            'videos'            => 'camera-video',
            'contact'           => 'envelope',
            'help'              => 'question-circle',
            'familynews'        => 'newspaper',
            'prayers'           => 'book',
            'recipes'           => 'card-checklist',
            'familytree'        => 'diagram-3',
            'documents'         => 'file-earmark-word',
            'admin.upgrade'     => 'cloud-arrow-up',
            'admin.config'      => 'gear',
            'admin.members'     => 'person-gear',
            'admin.photos'      => 'file-earmark-image',
            'admin.polls'       => 'patch-question',
            'admin.facebook'    => 'facebook',
            'admin.google'      => 'google',
            'admin.instagram'   => 'instagram',
        ];

        return $routeToIconLkup[$this->route_name];
    }
}
