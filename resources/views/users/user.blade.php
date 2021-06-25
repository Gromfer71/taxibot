@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Пользователь {{ $user->username }}</h1>
    <div>
        <a href="{{ route('users') }}" class="btn">Назад</a>   
    </div>   
</div>

<div class="row">
    <div class="col-8">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h2 class="h4">История заказов </h2>
            <div>  
                <a class="btn btn-danger btn-sm" href="{{ route('user_orders_clear', $user->id) }}">Очистить историю</a>
            </div>   
        </div>
        <div id="orders"></div>    
    </div>
    <div class="col-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h2 class="h4">История адресов </h2>
            <div>  
                <a class="btn btn-danger btn-sm" href="{{ route('user_addresses_clear', $user->id) }}">Очистить историю</a>
            </div>   

        </div>
        <div id="addresses"></div>    
    </div>
</div>

@endsection
@push('scripts')
    <script>



        document.addEventListener("DOMContentLoaded", function() {
            let x = "{{ $orders }}"
            data1 = x.replace(/&quot;/g, '"')
            data1 = data1.replace(/[\r\n]/g, " ");
            data1 = data1.replace(/\\/g, '');
            data1 = JSON.parse(data1)

            let prices = "{{ $prices }}"
            prices = prices.replace(/&quot;/g, '"')
            prices = prices.replace(/[\r\n]/g, " ");
            prices = prices.replace(/\\|\//g, '');
            prices = JSON.parse(prices)









            new FancyGrid({
                theme: 'bootstrap',
                renderTo: 'orders',
                height: 800,
                defaults: {
                    resizable: true,
                    autoHeight: true,
                    sortable: true,
                    width: 100,

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
                    title: 'ID',
                    type: 'string',

                },{
                    index: 'created_at',
                    title: 'Date',
                    type: 'string',

                },{
                    index: 'phone',
                    title: 'Phone',
                    type: 'string',

                },{
                    index: 'address',
                    title: 'Addresses',
                },
                    {
                        index: 'price',
                        title: 'Price',
                    },
                    {
                        index: 'changed_price',
                        title: 'Changed price',
                        render: function (o) {
                             Array.from(prices).filter(function (item)  {
                                if(item.id == o.value) {
                                    o.value = item.value
                                }
                            })

                            return o;
                        }
                    },
                    {
                        index: 'comment',
                        title: 'Comment',
                    },
                    {
                        index: 'wishes',
                        title: 'Wishes',
                        width: 400,
                    },
                    {
                        index: 'usebonus',
                        title: 'Used bonus',
                    },

                ]
            });
        });

        x = "{{ $addresses }}"
        data1 = x.replace(/&quot;/g, '"')
        data1 = data1.replace(/[\r\n]/g, " ");
        data1 = data1.replace(/\\/g, '');
        data1 = JSON.parse(data1)

        new FancyGrid({
            theme: 'bootstrap',
            renderTo: 'addresses',
            height: 800,
            defaults: {
                resizable: true,
                autoHeight: true,
                sortable: true,
                width: 200,

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
                title: 'ID',
                type: 'string',

            },{
                index: 'address',
                title: 'Addresses',
                type: 'string',

            },

            ]
        });
    </script>
@endpush