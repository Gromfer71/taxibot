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
@endsection
@push('scripts')
    <script>



        document.addEventListener("DOMContentLoaded", function() {




            let x = "{{ $users }}"

            data1 = x.replace(/&quot;/g, '"')
            data1 = data1.replace(/[\r\n]/g, " ");
            data1 = data1.replace(/\\|\//g, '');
            data1 = JSON.parse(data1)


            new FancyGrid({
                theme: 'bootstrap',
                renderTo: 'users',
                height: 800,
                defaults: {
                    resizable: true,
                    autoHeight: true,
                    sortable: true,
                    width: 200,
                    render: function (o) {
                        o.value = '<a href="users/' + o.data.id + '">' + o.value + '</a>'
                        return o;
                    }
                },
                paging: {
                    pageSize: 20,
                },

                tbar: [{
                    type: 'search',
                    width: 350,
                    emptyText: 'Поиск',
                    paramsMenu: true,
                    paramsText: 'Параметры'
                }],
                data: data1,
                columns: [{
                    index: 'id',
                    title: 'ID_telegram',
                    type: 'string',

                },{
                    index: 'username',
                    title: 'User',
                    type: 'string',

                },{
                    index: 'phone',
                    title: 'Phone',
                    type: 'string',

                },
                    {
                        index: 'isBlocked',
                        title: 'Заблокирован',
                    },

                    {
                    index: '',
                    title: 'Action',
                    render: function (o) {
                            o.value = '<a href="/users/' + o.data.id + '/delete"><button class="btn btn-sm btn-danger uk-margin-small-right">Удалить</button></a>&#160;';

                        if(o.data.isBlocked == 0) {
                            o.value += '  <a href="/users/' + o.data.id + '/block"><button class="btn btn-sm btn-danger">Заблокировать</button></a>'
                        } else {
                            o.value += '  <a href="/users/' + o.data.id + '/unblock"><button class="btn btn-sm btn-danger">Разблокировать</button></a>'
                        }

                        return o;
                    }

                },

                ]
            });
        });

    </script>
    @endpush