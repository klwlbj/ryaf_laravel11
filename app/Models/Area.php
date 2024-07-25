<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Area extends BaseModel
{
    use HasFactory;

    public function parentArea()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('id');
    }

    public function loadChildren()
    {
        $this->load('children');

        $this->children->each(function ($child) {
            $child->loadChildren();
        });
    }
}
