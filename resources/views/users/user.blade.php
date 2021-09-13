@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Пользователь {{ $user->username }}</h1>
        <div>
            <a href="{{ route('users') }}" class="btn">Назад</a>
        </div>
    </div>

    <div class="uk-margin-medium">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h2 class="h4">История адресов </h2>
            <div>
                <a class="btn btn-danger btn-sm" href="{{ route('user_orders_clear', $user->id) }}">Очистить историю
                    адресов</a>
            </div>
        </div>
        <div class="layer uk-margin-top" style="overflow-x: scroll; white-space: nowrap;">
            <table id="addresses-table" class="display">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Адрес</th>
                </tr>
                </thead>
                <tbody>
                @foreach($user->addresses as $address)
                    <tr>
                        <td>{{ $address->id }}</td>
                        <td>{{ $address->address }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="uk-margin-medium">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h2 class="h4">История Заказов</h2>
            <div>
                <a class="btn btn-danger btn-sm" href="{{ route('user_addresses_clear', $user->id) }}">Очистить историю
                    заказов</a>
            </div>
        </div>
        <div class="layer uk-margin-top" style="overflow-x: scroll;">
            <table id="orders-table" class="display">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Маршрут</th>
                    <th>Цена</th>
                    <th>Изменение цены</th>
                    <th>Комментарий</th>
                    <th>Пожелания</th>
                    <th>Дата</th>
                </tr>
                </thead>
                <tbody>
                @foreach($user->orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->address }}</td>
                        <td>{{ $order->price }}</td>
                        {{--     Имзенение цены берется из конфига, а хранится по id. Фильтруем  --}}
                        <td>{{ $prices->filter(function ($item) use ($order) {
                                    return $item->id == $order->changed_price;
                                })->first()->name ?? ''
                               }}</td>
                        <td>{{ $order->comment }}</td>
                        <td>{{ $order->wishes }}</td>
                        <td>{{ $order->created_at }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $.noConflict();

            $('table.display').DataTable({
                "autoWidth": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.1/i18n/ru.json"
                },
            });
        });
    </script>
@endpush