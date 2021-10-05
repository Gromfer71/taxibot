@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Настройки</h1>
    </div>

    <div class="mb-3">
        <form action="{{ route('change_token') }}" method="POST">
            @csrf
            <label for="username">Токен</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">key</span>
                </div>
                <input type="text" class="form-control" name="token" placeholder="новый токен (API телеграм ключ)"
                       required value="{{ $token->value }}">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-secondary">Изменить токен</button>
                </div>
            </div>
        </form>
    </div>

    <div class="mb-3">
        <form action="{{ route('change_config_file') }}" method="POST">
            @csrf
            <label for="config">Файл конфигурации</label>
            <pre><textarea type="text" name="config" id="settings" required
                           style="height: 500px;" class="form-control">{{ $config }}</textarea></pre>
            <button type="submit" class="btn btn-primary">Сохранить</button>
        </form>
    </div>

    <div class="mb-3">
        <form action="{{ route('admins_create') }}" method="POST">
            <div class="mb-3">
                @csrf
                <label for="config">Добавить администратора</label>
                <input type="tel" pattern="[789][0-9]{10}" name="phone" placeholder="Номер телефона" required
                       class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Добавить</button>
        </form>
    </div>

    <div class="layer uk-margin-top" style="overflow-x: scroll; white-space: nowrap;">
        <table id="admins-table" class="display">
            <thead>
            <tr>
                <th>Телефон</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            @foreach($admins as $admin)
                <tr>
                    <td>{{ $admin->phone }}</td>
                    <td>
                        <a href="{{ route('admins_destroy', $admin->phone) }}" class="btn btn-danger"
                           onclick="return confirm('Вы действительно хотите удалить пользователя?')">Удалить</a>
                        <button class="btn btn-primary change-password" uk-toggle="target: #change_password"
                                data-phone="{{ $admin->phone }}">
                            Изменить пароль
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{--    Модальное окно для смены пароля --}}
    <div id="change_password" uk-modal>
        <div class="uk-modal-dialog uk-modal-body">
            <h2 class="uk-modal-title">Новый пароль</h2>
            <form action="{{ route('change_password') }}" method="POST">
                @csrf
                <input type="hidden" name="phone" id="phone">
                <input type="text" name="new_password" class="uk-input" required><br><br>
                <button class="uk-button uk-button-primary" type="submit">Сохранить</button>
            </form>
            <button class="uk-modal-close-default" type="button" uk-close></button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $.noConflict();

            var myJsObj = JSON.parse($('#settings').val())
            var str = JSON.stringify(myJsObj, undefined, 2);
            $('#settings').val(str)

            $('.change-password').on('click', function () {
                $('#phone').val($(this).data('phone'))
            })

            $('#admins-table').DataTable({
                "autoWidth": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.1/i18n/ru.json"
                },
            });
        });
    </script>
@endpush


