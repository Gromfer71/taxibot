@extends('layouts.app')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Журнал ошибок бота</h1>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h4">Почтовые адреса для отправки уведомлений (строго через запятую)</h2>
                <div>

                </div>
            </div>

            <form action="{{ route('update_emails') }}" method="POST">
                @csrf
                <input type="text" value="{{ $emails }}" name="emails" class="uk-input uk-margin">
                <button type="submit" class="btn btn-success uk-margin-bottom">Сохранить</button>

            </form>
            <div>

            </div>
            <a href="{{ route('clear_error_reports') }}" class="btn btn-success uk-margin-bottom">Очистить журнал</a>
        </div>
    </div>

    <div class="layer uk-margin-top" style="overflow-x: scroll;">
        <table id="table" class="display">
            <thead>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>Сообщение об ошибке</th>
                <th>Стек вызовов функций</th>
                <th>Дата</th>
            </tr>
            </thead>
            <tbody>
            @foreach($errors as $error)
                <tr>
                    <td>{{ $error->id }}</td>
                    <td>{{ $error->user->username ?? 'Неавторизованный пользователь' }}</td>
                    <td>{{ $error->error_message }}</td>
                    <td class="stack-trace"
                        data-trace="{{ $error->stack_trace }}">{{ substr($error->stack_trace, 0, 80) . '...' }}</td>
                    <td>{{ $error->created_at }}</td>

                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

@endsection
@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            $.noConflict();

            $('table.display').DataTable({
                "order": [],
                "autoWidth":
                    true,
                "language":
                    {
                        "url":
                            "//cdn.datatables.net/plug-ins/1.11.1/i18n/ru.json"
                    }
                ,
            })


            $('.stack-trace').on('click', function () {
                alert($(this).data('trace'))
            })
        });
    </script>
@endpush