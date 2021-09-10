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
        $this->hasOne(User::class, 'id', 'user_id');
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
                Mail::send(
                    'adminPanel.email_error_report',
                    ['report' => $this],
                    function ($message) use ($email) {
                        $message->to($email, $email)->subject('Уведомление об отловленной ошибке в чат-боте СК-такси');
                        $message->from(env('MAIL_USERNAME'), 'Чат-бот СК-такси');
                    }
                );
            }
        }
    }


}
