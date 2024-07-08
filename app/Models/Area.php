<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Area extends Model
{
    use HasFactory;

    public function parentArea()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('id', 'asc');
    }

    public function loadChildren()
    {
        $this->load('children');

        $this->children->each(function($child) {
            $child->loadChildren();
        });
    }
}
