<?php

Route::get('/', function () {
    return redirect(route('login'));
});

Auth::routes([
                 'register' => false, // Registration Routes...
                 'reset' => false, // Password Reset Routes...
                 'verify' => false, // Email Verification Routes...
             ]);
Route::match(['get', 'post'], 'login', 'Auth\LoginController@login')->name('login');


Route::match(['get', 'post'], '/botman', 'BotManController@handle');

Route::get('/botman/tinker', 'BotManController@tinker');


Route::group(['midlleware' => 'auth'], function () {
    Route::get('/home', function () {
        return redirect(route('users'));
    })->name('home');

    Route::get('bot_settings', 'BotSettingsController@index')->name('bot_settings');
    Route::post('change_token', 'BotSettingsController@changeToken')->name('change_token');
    Route::post('change_config_file', 'BotSettingsController@changeConfigFile')->name('change_config_file');
    Route::post('upload_welcome_file_telegram', 'BotSettingsController@uploadWelcomeFileTelegram')->name('upload-welcome-file-telegram');
    Route::get('delete_welcome_file', 'BotSettingsController@deleteWelcomeFileTelegram')->name('delete-welcome-file-telegram');


    Route::post('upload_welcome_file_vk', 'BotSettingsController@uploadWelcomeFileVk')->name('upload-welcome-file-vk');
    Route::get('delete_welcome_file_vk', 'BotSettingsController@deleteWelcomeFileVk')->name('delete-welcome-file-vk');


    Route::post('admins/create', 'AdminController@create')->name('admins_create');
    Route::get('admins/destroy/{phone}', 'AdminController@destroy')->name('admins_destroy');

    Route::get('users', 'UserController@index')->name('users');
    Route::post('add_user', 'UserController@addUser')->name('add_user');
    Route::get('users/{id}', 'UserController@user')->name('user');
    Route::get('users/{id}/orders', 'UserController@orders')->name('user_orders');

    Route::get('users/{id}/addresses', 'UserController@addresses')->name('user_addresses');
    Route::get('users/{id}/delete', 'UserController@delete')->name('user_delete');
    Route::get('users/{id}/reset', 'UserController@reset')->name('user_reset');
    Route::get('users/{id}/block', 'UserController@block')->name('user_block');
    Route::get('users/{id}/unblock', 'UserController@unblock')->name('user_unblock');
    Route::post('users/change_password', 'UserController@changePassword')->name('change_password');

    Route::get('users/{id}/orders/clear', 'UserController@ordersClear')->name('user_orders_clear');
    Route::get('users/{id}/addresses/clear', 'UserController@addressesClear')->name('user_addresses_clear');
    Route::get('addresses/{id}/delete', 'UserController@deleteAddress')->name('user_delete_address');
    Route::get('orders/{id}/delete', 'OrderController@delete')->name('order_delete');

    Route::get('error_reports', 'ErrorReportController@index')->name('error_reports');
    Route::get('error_reports/clear', 'ErrorReportController@clear')->name('clear_error_reports');
    Route::post('error_reports/update_emails', 'ErrorReportController@updateEmails')->name('update_emails');

    Route::match(['get', 'post'], 'bot_settings/edit_messages', 'BotSettingsController@editMessages')->name('edit_messages');
    Route::match(['get', 'post'], 'bot_settings/edit_buttons', 'BotSettingsController@editButtons')->name('edit_buttons');

    Route::get('/messages/index', 'MessagesController@index')->name('messages.index');
    Route::post('/messages/send', 'MessagesController@send')->name('messages.send');
    Route::get('/messages/delete/{id}', 'MessagesController@deleteMessage')->name('messages.delete-message');
    Route::get('/messages/clearAllMessages', 'MessagesController@clearAllMessages')->name('messages.clear-all-messages');
});

