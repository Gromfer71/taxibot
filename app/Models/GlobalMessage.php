<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalMessage extends Model
{
    protected $guarded = [];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }
}