<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

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
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                \mail($email, 'Уведомление об ошибке в чат-боте', 'Здравствуйте. В журнал ошибок чат-бота добавлена новая запись.');
            }
        }
    }

}
