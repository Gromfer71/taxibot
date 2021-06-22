<?php

namespace App\Models;

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
 * @method static \Illuminate\Database\Eloquent\Builder|Log newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Log newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Log query()
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereIsBot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereMessageValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereUserId($value)
 * @mixin \Eloquent
 */
class Log extends Model
{
	protected $table = 'logs';
    protected $guarded = [];
    public $timestamps = false;

    public static function newLogAnswer($bot, $answer)
    {
    	return self::create([
		    'user_id' => $bot->getUser()->getId(),
		    'message' => $answer->getText(),
		    'message_value' => $answer->isInteractiveMessageReply() ? $answer->getValue() : null,
		    'isBot' => 0,
	    ]);
    }

    public static function newLogDebug($bot, $message)
    {
        return self::create([
            'user_id' => $bot->getUser()->getId(),
            'message' => $message,
            'message_value' => null,
            'isBot' => 1,
        ]);
    }
}
