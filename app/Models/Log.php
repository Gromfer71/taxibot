<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Log
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $isBot
 * @property string|null $message
 * @property string|null $message_value
 * @property string|null $created_at
 * @property string|null $updated_at
 * @method static Builder|Log newModelQuery()
 * @method static Builder|Log newQuery()
 * @method static Builder|Log query()
 * @method static Builder|Log whereCreatedAt($value)
 * @method static Builder|Log whereId($value)
 * @method static Builder|Log whereIsBot($value)
 * @method static Builder|Log whereMessage($value)
 * @method static Builder|Log whereMessageValue($value)
 * @method static Builder|Log whereUpdatedAt($value)
 * @method static Builder|Log whereUserId($value)
 * @mixin \Eloquent
 */
class Log extends Model
{
    protected $table = 'logs';
    protected $guarded = [];

    public static function newLogAnswer($userId, $message, $value)
    {
        return self::create([
                                'user_id' => $userId,
                                'message' => $message,
                                'message_value' => $value,
                                'isBot' => 0,

                            ]);
    }

    public static function newLogDebug($userId, $value)
    {
        return self::create([
                                'user_id' => $userId,
                                'message' => $value,
                                'message_value' => null,
                                'isBot' => 1,
                            ]);
    }
}
