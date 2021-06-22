@extends('alayouts.app')

@section('content')
<form method="POST" action="{{ route('confirm_login') }}" class="form-signin">
        <img class="mb-4 rounded border" src="https://sk-taxi.ru/bitrix/templates/main/img/logo.svg" alt="logo" width="72" height="72">
        
        <h1 class="h3 mb-3 font-weight-normal">Подтверждение авторизации</h1>
        
        <label for="inputEmail" class="sr-only">СМС-код</label>
        @csrf
        <input type="text" class="form-control" type="code" name="sms_code" placeholder="****" required>
        <input type="hidden"  name="phone" value="{{ $phone }}" required> 
        <span class="small mb-3 d-flex">
            На ваш телефон отправлено смс уведомление. Если оно не пришло, попробуйте ввести из последнего смс.
        </span>           
        <button type="submit" class="btn btn-lg btn-primary btn-block">Войти</button>

        <p class="mt-5 mb-3 text-muted">© 2021</p>
    </form>
@endsection
