<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function read()
    {
        return json_encode(Admin::all()->toArray());
    }

    public function create(Request $request)
    {
       $admin = Admin::create(['phone' => $request->get('phone')]);
       $admin->save();

       return back()->with('ok', 'Администратор создан');
    }

    public function destroy($phone)
    {
        Admin::find($phone)->first()->delete();

        return back()->with('ok', 'Администратор удален');
    }
}
