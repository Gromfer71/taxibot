@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom uk-margin-bottom">
        <h1 class="h2">Пользователи</h1>
    </div>

    <form action="{{ route('add_user') }}" method="POST">
        @csrf
        <br>
        <input type="text" required name="phone" class="form-control" pattern="[0-9]{10}"
               placeholder="Номер телефона (в формате 9ХХХХХХХХХ)">
        <br>
        <button type="submit" class="btn btn-primary">Добавить пользователя</button>
        <br><br>
    </form>

    <div class="layer uk-padding-large" style="overflow-x: scroll; white-space: nowrap;">
        <table id="table_id" class="display">
            <thead>
            <tr>
                <th>ID</th>
                <th>Дата регистрации</th>
                <th>Телеграм ID</th>
                <th>ВК ID</th>
                <th>Логин</th>
                <th>Телефон</th>
                <th>Заблокирован</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->id ?: '-' }}</td>
                    <td>{{ $user->created_at->timestamp }}</td>
                    <td>{{ $user->telegram_id ?: '-' }}</td>
                    <td>{{ $user->vk_id ?: '-' }}</td>
                    <td>{{ $user->username }}</td>
                    <td><a href="{{ route('user', $user->id) }}">{{ $user->phone }}</a></td>
                    <td>{{ $user->isBlocked ? 'Да' : 'Нет' }}</td>
                    <td>
                        <a href="{{ route('user_reset', $user->id) }}" class="btn btn-secondary">Сбросить</a>
                        <a href="{{ route('user_delete', $user->id) }}" class="btn btn-danger">Удалить</a>
                        @if($user->isBlocked)
                            <a href="{{ route('user_unblock', $user->id) }}" class="btn btn-warning">Разблокировать</a>
                        @else
                            <a href="{{ route('user_block', $user->id) }}" class="btn btn-primary">Заблокировать</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $.noConflict();
            $('#table_id').DataTable({
                "autoWidth": true,
                order: [[0, 'desc']],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.1/i18n/ru.json"
                },
            });
        });
    </script>
@endpush