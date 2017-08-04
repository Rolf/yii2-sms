<?php
/**
 * Created by PhpStorm.
 * User: kaiser
 * Date: 30.11.15
 * Time: 15:19
 */

namespace app\services\common\sms;

use app\components\validators\PhoneValidator;
use app\helpers\Format;
use app\helpers\WhiteListHelper;
use app\models\user\User;
use app\services\common\exceptions\HumanFriendlyException;
use app\services\common\request\PipoRequestService;

/**
 * Сервис для проверки смс перед отправкой и отправки
 */
class Sms
{
    /**
     * Отправляет SMS сообщение
     *
     * @param string $phone Телефон кому отправляем СМС должен быть в формате +<номер телефона в международном формате>
     * @param string $message Текст SMS сообщение
     * @param bool $isFreeSms - бесплатное ли SMS сообщение
     * @param User|null $user - Пользователь, который платит, если это платная смс
     * @param string $type - Тип смс
     * @return bool
     * @throws \Exception
     */
    public static function send($phone, $message, $isFreeSms, User $user = null, $type = '')
    {
        \Yii::info('', __METHOD__);
        \Yii::info('Отправка SMS', __METHOD__);
        \Yii::info('Пользователь: ' . Format::logUserInfo($user), __METHOD__);
        \Yii::info('Телефон: ' . $phone, __METHOD__);
        \Yii::info('Текст сообщения: ' . $message, __METHOD__);
        \Yii::info('Бесплатная ли SMS: ' . $isFreeSms, __METHOD__);
        \Yii::info('Тип SMS: ' . $type, __METHOD__);

        // индикатор успешности выполнения задачи. Если хоть на один номер ушла СМС, то задача считается успешной
        $successTask = true;

        // инициализация переменной под номер отправленного СМС на шлюзе
        $taskId = null;

        // Возможность указывать несколько телефонов через запятую
        $phones = self::preparePhone($phone);

        \Yii::info('Будем отправлять СМС на номера:' . Format::logArrayToString($phones), __METHOD__);

        foreach ($phones as $phone) {
            try {
                    \Yii::info('Проверки норм, пробуем отправить запрос', __METHOD__);
                    $taskId = SmsSender::send($phone, $message);
            } catch (\Exception $e) {
                \Yii::info('Произошла ошибка отправки СМС на номер ' . $phone, __METHOD__);

                \Yii::info('Исключение', __METHOD__);
                \Yii::info('Сообщение: '.$e->getMessage(), __METHOD__);
                \Yii::info('Код: '.$e->getCode(), __METHOD__);
                \Yii::info('Файл: '.$e->getFile(), __METHOD__);
                \Yii::info('Строка: '.$e->getLine(), __METHOD__);
                \Yii::info("Трейс: \n".$e->getTraceAsString(), __METHOD__);

                $successTask = false;
                // ничего не делаем, ошибку пробрасываем только в конце
                $exception = $e;
            }
        }

        if ($successTask === false) {
            throw $exception;
        }

        return $taskId;
    }

    /**
     * Проверяем телефоны на корректность для отправки
     * @param string $phone номера телефонов
     * @return array
     */
    private static function preparePhone($phone)
    {
        // Возможность указывать несколько телефонов через запятую
        $phones = array_map(
            function ($phone) {
                return trim($phone);
            },
            explode(',', $phone)
        );

        try {
            // Отфильтровать адреса по белому списку (только в dev-окружении)
            $phones = array_filter($phones, function (string $phone) {
                \Yii::info('Проверяем номер телефона ' . $phone, __METHOD__);
                $phone = Format::phonePlus(Format::phone($phone));
                // Валидируем телефон что он к нам пришёл корректный
                $phoneValidator = new PhoneValidator();
                $phoneValidator->validate($phone);
                \Yii::info('Ищем в белом списке номер телефона ' . $phone, __METHOD__);
                return WhiteListHelper::checkPhone($phone);
            });
        } catch (\Exception $e) {
            \Yii::info('Не корректный номер телефона ', __METHOD__);
        }

        return $phones;
    }
}
