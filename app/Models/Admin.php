<?php

namespace App\Models;


use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticableTrait;

/**
 * App\Models\Admin
 *
 * @property int $phone
 * @property string|null $remember_token
 * @property string $password
 * @method static \Illuminate\Database\Eloquent\Builder|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin query()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereRememberToken($value)
 * @mixin \Eloquent
 */
class Admin  extends \Eloquent implements Authenticatable
{
    use AuthenticableTrait;
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'phone';
}
