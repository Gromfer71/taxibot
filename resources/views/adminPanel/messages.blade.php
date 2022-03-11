@extends('layouts.app')

@section('content')
    <div>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom uk-margin-bottom">
            <h1 class="h2">Рассылка сообщений</h1>
        </div>
        <form action="{{ route('messages.send') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <select name="type" class="form-control mb-2">
                <option value="all">Всем</option>
                <option value="telegram">Только телеграм</option>
                <option value="vk">Только вк</option>
            </select>
            <select name="recipients" id="recipients" class="form-control mb-2">
                <option value="all">Всем</option>
                <option value="by_city">По городам</option>
                <option value="by_phone">Персонально</option>
            </select>

            <textarea name="message" class="form-control" cols="30" rows="10"></textarea>
            <input type="file" name="file" class="form-control-file mt-2">
            {{ session('errors') }}

            <button class="btn btn-success mb-2 mt-2" type="submit">Отправить</button>
        </form>
    </div>
@endsection