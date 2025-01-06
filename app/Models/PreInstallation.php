<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class PreInstallation extends BaseModel
{
    use SoftDeletes;
    protected $table   = 'pre_installation';
    public $timestamps = true;
}
