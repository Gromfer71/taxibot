<?php

declare(strict_types=1);

namespace Tests\Bot\MainMenu\FavoriteAddresses;

use Tests\Bot\MainMenu\MainMenuTest;

/**
 *  Тест добавления избранного адреса и его удаления
 */
class AddNewFavoriteAddress extends MainMenuTest
{
    /**
     * Начало запуска
     */
    public function run()
    {
        $this->goToFavoriteAddressesMenu();
        $this->goToAddAddress();
        $this->writeAddress();
        $this->chooseAddressFromApi();
        $this->enterEntrance();
        $this->enterFavoriteAddressNameTooLong();
        $this->enterFavoriteAddressName();
        $this->save();
        $this->selectCreatedAddress();
        $this->delete();
        $this->back();
    }

    /**
     * Переходим в меню избранных адресов
     */
    private function goToFavoriteAddressesMenu()
    {
        $this->sendMessage(trans('buttons.favorite addresses menu'));
        $this->assertEquals(trans('messages.favorite addresses menu'), $this->getBotResponse());
    }

    /**
     * Переходим в меню добавления избранного адреса
     */
    private function goToAddAddress()
    {
        $this->sendMessage(trans('buttons.add address'));
        $this->assertEquals(trans('messages.give me your favorite address'), $this->getBotResponse());
    }

    /**
     * Пишем желаемый адрес, который хотим добавить
     */
    private function writeAddress()
    {
        $address = 'лен2';
        $this->sendMessage($address);
        $this->assertEquals(trans('messages.give favorite address again'), $this->getBotResponse());
    }

    /**
     * После ввода появляется клавиатура с предложенными вариантами, выбираем заранее подготовленный адрес
     */
    private function chooseAddressFromApi()
    {
        $address = 'Ленина пр-т 2 (Якутск)';
        $this->sendMessage($address);
        $this->assertEquals(trans('messages.give entrance in favorite address'), $this->getBotResponse());
    }

    /**
     * Вводим номер подъезда
     */
    private function enterEntrance()
    {
        $this->sendMessage('2');
        $this->assertEquals(trans('messages.get address name'), $this->getBotResponse());
    }

    /**
     * Вводим очень длинный псевдоним для нашего избранного адреса. Ожидаем повторный запрос псевдонима
     */
    private function enterFavoriteAddressNameTooLong()
    {
        $this->sendMessage('домдомдомдомдомдомдомдомдомдомдомдомдомдомдомдом
        домдомдомдомдомдомдомдомдомдомдомдомдомдомдомдомдомдомдомдомдомдомдом
        домдомдомдомдом');
        $this->assertEquals('А теперь давайте его как-нибудь обзовём. Напишите название и отправьте его мне (не более 32 символов)', $this->getBotResponse());
    }

    /**
     * Вводим псевдоним для нашего избранного адреса (нормальный)
     */
    private function enterFavoriteAddressName()
    {
        $this->sendMessage('дом');
        $this->assertEquals('дом Ленина пр-т 2 (Якутск), *п 2', $this->getBotResponse());
    }

    /**
     * Сохраняем новый избранный адрес и проверяем, что мы действительно перешли в меню избранных адресов
     */
    private function save()
    {
        $this->sendMessage(trans('buttons.save'));
        $this->assertEquals(trans('messages.favorite addresses menu'), $this->getBotResponse());
    }

    /**
     * Выбираем из меню только что созданный адрес
     */
    private function selectCreatedAddress()
    {
        $this->sendMessage('дом (Ленская ул. 2 (Якутск), *п 2)');
        $this->assertEquals(trans('messages.favorite address menu'), $this->getBotResponse());
    }

    /**
     * Выбираем опцию удаления адреса
     */
    private function delete()
    {
        $this->sendMessage(trans('buttons.delete'));
        $this->assertEquals(trans('messages.favorite addresses menu'), $this->getBotResponse());

    }

    /**
     * Возвращаемся назад
     */
    private function back()
    {
        $this->sendMessage(trans('buttons.back'));
        $this->checkUserInMainMenu();
    }
}