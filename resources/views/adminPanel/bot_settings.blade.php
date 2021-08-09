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
                        <input type="text" class="form-control" name="token" placeholder="новый токен (API телеграм ключ)" required
                       value="{{ $token->value }}">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-secondary">Изменить токен</button>
                    </div>
                </div>
        </form>
    </div>


    <div class="mb-3">
        <form action="{{ route('change_config_file') }}" method="POST">
            <div class="mb-3">
            @csrf
            <label for="config">Файл конфигурации</label>
            <pre><textarea type="text" name="config" id="settings" required
                                 style="height: 500px;" class="form-control">{{ $config }}</textarea></pre>
            
            </div>
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

    <div id="container"></div>
@endsection
@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("setting").value = JSON.stringify(JSON.parse(document.getElementById("settings").value), null ,2)
             var settings = null;

            try {
                settings = JSON.parse(field.value);
            }
            catch (error) {
                if (error instanceof SyntaxError) {
                    alert("There was a syntax error. Please correct it and try again: " + error.message);
                }
                else {
                    throw error;
                }
            }
            new FancyGrid({
                theme: 'bootstrap',
                renderTo: 'container',
                height: 500,
                paging: {
                    pageSize: 20,
                },
                data: {
                    proxy: {
                        api: {

                            read: '{{ route('admins_read') }}',
                        }
                    }
                },


                tbar: [],
                defaults: {
                    resizable: true,
                    sortable: true,
                    width: 320,
                    height: 300,
                },

                columns: [
                   {
                        index: 'phone',
                        title: 'Телефон',


                    },
                    {
                        autoHeight: true,
                        index: '',
                        title: 'Действия',
                        render: function (o) {
                            o.value = '<a href="/admins/destroy/' + o.data.phone + '"><button class="btn btn-sm btn-danger">Удалить</button></a>';

                            return o;
                        }
                    }
                ]
            });
        });
    </script>
@endpush


