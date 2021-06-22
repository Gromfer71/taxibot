<?php

Route::get('/', function () {
    return redirect(route('login'));
});

Auth::routes([
    'register' => false, // Registration Routes...
    'reset' => false, // Password Reset Routes...
    'verify' => false, // Email Verification Routes...
]);
Route::match(['get', 'post'], 'sms_confirm_login', 'Auth\LoginController@confirmLogin')->name('confirm_login');
Route::get( 'sendSms', 'Auth\LoginController@sendSms')->name('send_sms');

Route::get( 'tinker', 'BotManController@tinker')->name('tinker');

Route::match(['get', 'post'], '/botman', 'BotManController@handle');
Route::get('/botman/tinker', 'BotManController@tinker');


Route::group(['midlleware' => 'auth'], function() {
    Route::get('/home', function () {
        return redirect(route('bot_settings'));
    })->name('home');

    Route::get('bot_settings', 'BotSettingsController@index')->name('bot_settings');
    Route::post('change_token', 'BotSettingsController@changeToken')->name('change_token');
    Route::post('change_config_file', 'BotSettingsController@changeConfigFile')->name('change_config_file');



    Route::get('admins/read', 'AdminController@read')->name('admins_read');
    Route::post('admins/create', 'AdminController@create')->name('admins_create');
    Route::get('admins/destroy/{id}', 'AdminController@destroy')->name('admins_destroy');

    Route::get('users', 'UserController@index')->name('users');
    Route::get('users/{id}', 'UserController@user')->name('user');
    Route::get('users/{id}/orders', 'UserController@orders')->name('user_orders');
    Route::get('users/{id}/addresses', 'UserController@addresses')->name('user_addresses');
    Route::get('users/{id}/delete', 'UserController@delete')->name('user_delete');

    Route::get('users/{id}/orders/clear', 'UserController@ordersClear')->name('user_orders_clear');
    Route::get('users/{id}/addresses/clear', 'UserController@addressesClear')->name('user_addresses_clear');

    Route::get('/bot_config', function () {
        return \App\Models\Config::getTaxibotConfig()->value;
    });

    Route::get('test', function () {
        return view('test');
    });

    Route::match(['get', 'post'], 'bot_settings/edit_messages', 'BotSettingsController@editMessages')->name('edit_messages');
    Route::match(['get', 'post'], 'bot_settings/edit_buttons', 'BotSettingsController@editButtons')->name('edit_buttons');
});

