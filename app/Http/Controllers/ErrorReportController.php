<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Models\ErrorReport;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ErrorReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('adminPanel.error_reports',
                    [
                        'emails' => Config::find('errorReportEmails')->value,
                        'errors' => ErrorReport::orderByDesc('id')->get()->transform(function ($item) {
                            $item->created_at = Carbon::make($item->created_at)->timezone('Asia/Irkutsk');
                        }),
                    ]
        );
    }


    public function updateEmails(Request $request)
    {
        Config::updateErrorReportEmails(str_replace(' ', '', $request->get('emails')));

        return back()->with('ok', 'Почтовые адреса успешно обновлены');
    }

    public function clear()
    {
        ErrorReport::query()->truncate();

        return back()->with('ok', 'Журнал успешно почищен');
    }
}
