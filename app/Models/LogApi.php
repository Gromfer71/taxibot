<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Log
 *
 * @property int $id
 * @property string|null $url
 * @property string|null $params
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $result
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi query()
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi whereParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi whereUrl($value)
 */
class LogApi extends Model
{
    protected $table = 'logs_api';
    protected $guarded = [];
    public $timestamps = true;

    public static function newLogApi($url, $params)
    {
        return self::create([
            'url' => $url,
            'params' => $params
        ]);
    }


}
