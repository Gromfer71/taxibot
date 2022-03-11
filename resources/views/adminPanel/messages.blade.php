@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/bbbootstrap/libraries@main/choices.min.css">
    <script src="https://cdn.jsdelivr.net/gh/bbbootstrap/libraries@main/choices.min.js"></script>
    <div>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom uk-margin-bottom">
            <h1 class="h2">Рассылка сообщений</h1>
        </div>
        <form action="{{ route('messages.send') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <select name="type" class="form-control mb-2">
                <option value="all">На все платформы</option>
                <option value="telegram">Только телеграм</option>
                <option value="vk">Только вк</option>
            </select>
            <select name="recipients" id="recipients" class="form-control mb-2">
                <option value="all">Всем</option>
                <option value="by_city">По городам</option>
                <option value="by_phone">Персонально</option>
            </select>
            <div class="cities mb-2" hidden>
                <select hidden name="cities[]" id="cities" class="uk-select" multiple>
                    @foreach($cities as $city)
                        <option value="{{ $city->name }}">{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>


            <div class="phones mb-2" hidden>
                <select name="phones[]" id="phones" class="uk-select" multiple>
                    @foreach($phones as $phone)
                        <option value="{{ $phone }}">{{ $phone }}</option>
                    @endforeach
                </select>
            </div>

            <textarea name="message" class="form-control" cols="30" rows="10"></textarea>
            <input type="file" name="file" class="form-control-file mt-2">

            <button class="btn btn-success mb-2 mt-2" type="submit">Отправить</button>
        </form>
    </div>

    <script>
        $(document).ready(function () {
            new Choices('#cities', {
                removeItemButton: true,
            });
            new Choices('#phones', {
                removeItemButton: true,
            });


            $('.cities').hide()
            $('.phones').hide()


            $('#recipients').change(function () {
                $('.phones').hide()
                $('.cities').hide()
                if ($(this).val() === 'by_city') {
                    $('.cities').removeAttr('hidden')
                    $('.cities').show()
                } else if ($(this).val() === 'by_phone') {
                    $('.phones').removeAttr('hidden')
                    $('.phones').show()
                }
            })


        });
    </script>
@endsection