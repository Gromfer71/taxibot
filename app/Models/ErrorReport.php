<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

/**
 * App\Models\ErrorReport
 *
 * @property int $id
 * @property int $user_id
 * @property string $error_message
 * @property string $stack_trace
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport whereStackTrace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport whereUserId($value)
 * @mixin \Eloquent
 */
class ErrorReport extends Model
{
    protected $guarded = ['created_at'];
    public $timestamps = ['created_at'];
    const UPDATED_AT = null;

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function setUpReport($e, $userId)
    {
        $this->user_id = $userId;
        $this->error_message = $e->getMessage();
        $this->stack_trace = $e->getTraceAsString();
        $this->save();

        $this->sendReport(Config::getErrorReportEmails());
    }

    public function sendReport($emails)
    {
        if(!$emails) {
            return;
        }
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                \mail($email, 'Уведомление об ошибке в чат-боте', 'Здравствуйте. В журнал ошибок чат-бота добавлена новая запись.');
            }
        }
    }

}
