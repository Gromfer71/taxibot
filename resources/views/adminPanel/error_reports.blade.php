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
            <div id="error_reports"></div>
        </div>

        @endsection
        @push('scripts')
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    $.ajax({
                        url: '/error_reports/get_reports',
                        method: 'post',
                        dataType: 'json',
                        data: {
                            "_token": "{{ csrf_token() }}",
                        },
                        success: function (data) {
                            var grid = new FancyGrid({
                                theme: 'material',
                                title: 'Журнал ошибок',
                                resizable: true,
                                textSelection: true,
                                renderTo: 'error_reports',
                                height: '750',
                                width: 'fit',
                                data: data,
                                events: [{
                                    cellclick: function (grid, o) {
                                        alert(o.data.stack_trace);
                                    },
                                }],
                                paging: {
                                    pageSize: 15,
                                },
                                defaults: {
                                    resizable: true,
                                    type: 'string',
                                    flex: 1,
                                    sortable: true,

                                },
                                columns: [
                                    {
                                        index: 'id',
                                        flex: 0.3,
                                        locked: true,
                                        title: '№',
                                    }, {
                                        index: 'userName',
                                        title: 'Пользователь (его логин, для вк id/логин)',
                                        flex: 0.7,
                                    }, {
                                        index: 'error_message',
                                        title: 'Сообщение об ошибке',
                                        flex: 1,
                                    }, {
                                        index: 'stack_trace',
                                        title: 'Стек вызовов функций',
                                        data: data,
                                    },{
                                      index: 'created_at',
                                        title: 'Время',
                                        flex: 0.3
                                    }]
                            })
                        }
                    });
                });


            </script>
    @endpush