@extends('layouts.app')

@section('content')

<form action="{{ route('edit_buttons') }}" method="POST">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Кнопки</h1>
        <div>
            <a href="{{ route('home') }}" class="btn">Назад</a>
            <button type="submit" class="btn btn-success">Сохранить</button>    
        </div>   
    </div>
    @csrf
    @foreach($labels as $key => $label)
    <div class="mt-3"> 
        {{ $key }}
        <input type="text" name="buttons[{{$key}}]" value="{{ $label }}" class="form-control">
    </div>
    @endforeach
</form>
@endsection