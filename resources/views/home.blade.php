@extends('layouts.app')

@section('content')
<div class="container">

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Главная</div>

                <div class="card-body">
                    <div class="uk-card uk-card-default uk-card-body">
                        <ul class="uk-nav-default uk-nav-parent-icon" uk-nav>

                            <li><a href="{{ route('bot_settings') }}"><span class="uk-margin-small-right" uk-icon="icon: table"></span>Настройки бота</a></li>
                            <li><a href="{{ route('users') }}"><span class="uk-margin-small-right" uk-icon="icon: table"></span>Пользователи</a></li>
                            <li><a href="{{ route('edit_messages') }}"><span class="uk-margin-small-right" uk-icon="icon: table"></span>Редатировать сообщения бота</a></li>
                            <li><a href="{{ route('edit_buttons') }}"><span class="uk-margin-small-right" uk-icon="icon: table"></span>Редатировать кнопки бота</a></li>
                            <li class="uk-parent">
{{--                                <a href="#">Настройки бота</a>--}}
{{--                                <ul class="uk-nav-sub">--}}
{{--                                    <li><a href="tours/create">Создать</a></li>--}}
{{--                                    <li><a href="tours">Посмотреть</a></li>--}}
{{--                                </ul>--}}
                            </li>
                            <li class="uk-nav-divider"></li>
                            <li><a href="#"><span class="uk-margin-small-right" uk-icon="icon: user"></span> SK-taxi bot praweb</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
</div>
@endsection
