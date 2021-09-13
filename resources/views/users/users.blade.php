@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom uk-margin-bottom">
        <h1 class="h2">Пользователи</h1>

    </div>
    <form action="{{ route('add_user') }}" method="POST">
        @csrf
        <br>
        <input type="text" required name="phone" class="form-control" pattern="[0-9]{10}" placeholder="Номер телефона (в формате 9ХХХХХХХХХ)">
        <br>
        <button type="submit" class="btn btn-primary">Добавить пользователя</button>
        <br><br>
    </form>
    <div id="users"></div>
    <div class="layer" style="overflow-x: scroll; white-space: nowrap;">
        <table id="table_id" class="display">
            <thead>
            <tr>
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
        $(document).ready( function () {
            $.noConflict();
            $('#table_id').DataTable({
                "autoWidth": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.1/i18n/ru.json"
                },
            });
        } );
        {{--document.addEventListener("DOMContentLoaded", function() {--}}
        {{--    let x = "{{ $users }}"--}}

        {{--    data1 = x.replace(/&quot;/g, '"')--}}
        {{--    data1 = data1.replace(/[\r\n]/g, " ");--}}
        {{--    data1 = data1.replace(/\\|\//g, '');--}}
        {{--    data1 = JSON.parse(data1)--}}


        {{--    new FancyGrid({--}}
        {{--        events: [{--}}
        {{--            cellclick: function(grid, o){--}}
        {{--                document.location.href = "/users/" + o.data.id;--}}
        {{--            },--}}
        {{--        }],--}}
        {{--        theme: 'bootstrap',--}}
        {{--        renderTo: 'users',--}}
        {{--        height: 'fit',--}}
        {{--        defaults: {--}}
        {{--            resizable: true,--}}
        {{--            autoHeight: true,--}}
        {{--            sortable: true,--}}
        {{--            width: 200,--}}
        {{--            // render: function (o) {--}}
        {{--            //     o.value = '<a href="users/' + o.data.id + '"' + 'style="visibility: visible !important; opacity: 1 !important;">' + o.value + '</a>'--}}
        {{--            //     return o;--}}
        {{--            // }--}}
        {{--        },--}}
        {{--        paging: {--}}
        {{--            pageSize: 20,--}}
        {{--        },--}}

        {{--        tbar: [{--}}
        {{--            type: 'search',--}}
        {{--            width: 350,--}}
        {{--            emptyText: 'Поиск',--}}
        {{--            paramsMenu: true,--}}
        {{--            paramsText: 'Параметры'--}}
        {{--        }],--}}
        {{--        data: data1,--}}
        {{--        columns: [{--}}
        {{--            index: 'id',--}}
        {{--            title: 'ID_telegram',--}}
        {{--            type: 'string',--}}

        {{--        },{--}}
        {{--            index: 'username',--}}
        {{--            title: 'User',--}}
        {{--            type: 'string',--}}

        {{--        },{--}}
        {{--            index: 'phone',--}}
        {{--            title: 'Phone',--}}
        {{--            type: 'string',--}}

        {{--        },--}}
        {{--            {--}}
        {{--                index: 'isBlocked',--}}
        {{--                title: 'Заблокирован',--}}
        {{--            },--}}

        {{--            {--}}
        {{--            index: '',--}}
        {{--                width: 400,--}}
        {{--            title: 'Action',--}}
        {{--            render: function (o) {--}}
        {{--                o.value = '<a href="/users/' + o.data.id + '/reset"><button class="btn btn-sm btn-danger uk-margin-small-right">Сбросить</button></a>&#160;';--}}
        {{--                    o.value += '<a href="/users/' + o.data.id + '/delete"><button class="btn btn-sm btn-danger uk-margin-small-right">Удалить</button></a>&#160;';--}}

        {{--                if(o.data.isBlocked == 0) {--}}
        {{--                    o.value += '  <a href="/users/' + o.data.id + '/block"><button class="btn btn-sm btn-danger">Заблокировать</button></a>'--}}
        {{--                } else {--}}
        {{--                    o.value += '  <a href="/users/' + o.data.id + '/unblock"><button class="btn btn-sm btn-danger">Разблокировать</button></a>'--}}
        {{--                }--}}

        {{--                return o;--}}
        {{--            }--}}

        {{--        },--}}

        {{--        ]--}}
        {{--    });--}}
        {{--});--}}

    </script>
    @endpush