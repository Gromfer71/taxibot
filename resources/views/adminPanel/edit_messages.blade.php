@extends('layouts.app')

@section('content')
<form action="{{ route('edit_messages') }}" method="POST">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Сообщения</h1>
        <div>
            <a href="{{ route('home') }}" class="btn">Назад</a>
            <button type="submit" class="btn btn-success">Сохранить</button>    
        </div>   
    </div>
<div class="row">
    <div class="col-8">
        <div class="alert alert-warning" role="alert">
          Слова, перед которыми стоит двоеточие (например, ":price") означают, что вместо такого слова будет подставлено соответствующее значение.
        </div>
        <div class="mt-3">       
                @csrf
                @foreach($labels as $key => $label)
                    <div class="mb-3">
                        {{ \App\Models\Config::MESSAGE_LABELS[$key] ?? $key }}
                        <textarea type="text" name="messages[{{$key}}]"  class="form-control">{{ $label }}</textarea>
                    </div>
                @endforeach
        </div>
    </div>
    <div class="col-4">
        <div class="alert alert-info" role="alert">
        Доступные переменные
          <br>
          <code>:route</code> - маршрут
          <br>
          <code>:price</code> - стоимость поездки
          <br>
          <code>:address</code> - адрес
          <br>
          <code>:wishes</code> - пожелания
          <br>
          <code>:comment</code> - комментарий
          <br>
          <code>:phone</code> - телефон
          <br>
          <code>:city</code> - город
          <br>
          <code>:auto</code> - информация об авто
          <br>
          <code>:time</code> - время до подачи авто
        </div>
    </div>
</div>
</form>
@endsection