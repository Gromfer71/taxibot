<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Models\ErrorReport;
use Illuminate\Http\Request;

class ErrorReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('adminPanel.error_reports', ['emails' => Config::find('errorReportEmails')->value]);
    }

    public function getReports(Request $request)
    {
        return ErrorReport::orderByDesc('id')->get()->each(function ($item) {
            $item->userName = $item->user->username;
        })->toJson();
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
