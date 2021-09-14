@extends('alayouts.app')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="form-signin">
        <img class="mb-4 rounded border" src="https://sk-taxi.ru/bitrix/templates/main/img/logo.svg" alt="logo" width="72" height="72">
        
        <h1 class="h3 mb-3 font-weight-normal">Пожалуйста авторизуйтесь</h1>
        <label for="inputEmail" class="sr-only">Номер телефона</label>
        @csrf
        <input id="phone" type="tel" pattern="[789][0-9]{10}" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }}" name="phone" value="{{ old('name') }}" required autofocus="autofocus" placeholder="Phone">
        <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" value="{{ old('password') }}" required  placeholder="Password"><br>
        <button type="submit" class="btn btn-lg btn-primary btn-block">Войти</button>

        <p class="mt-5 mb-3 text-muted">© 2021</p>
    </form>

@endsection
