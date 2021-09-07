@extends('layouts.app')

@section('content')
   <table  class="table table-dark" >
      <tr>
          <th scope="col">
              Ошибка
          </th>
          <th scope="col">
              Должно быть
          </th>
          <th scope="col">
              Было
          </th>
          <th scope="col">
              Время ответа бота
          </th>
      </tr>
     @foreach($data as $error)
         @if($error['error'] != 'УСПЕШНО')
         <tr>
             <td>{{ $error['error'] }}</td>
             <td>{{ $error['should be'] }}</td>
             <td>{{ $error['was'] }}</td>
             <td>{{ $error['bot_response_time'] }}</td>
         </tr>
            @endif
       @endforeach
   </table>
   <p>Всего тестов {{ $data->count() }}</p>
   <p>Ошибок {{ $data->where('error', '!=', 'УСПЕШНО')->count() }}</p>

@endsection

