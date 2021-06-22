<?php

namespace App\Models;


use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticableTrait;

class Admin  extends \Eloquent implements Authenticatable
{
    use AuthenticableTrait;
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'phone';
}
