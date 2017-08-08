<?php
/**
 * Created by PhpStorm.
 * User: kaiser
 * Date: 30.11.15
 * Time: 15:19
 */

namespace bubogumy;

use yii\base\Component;

/**
 * Сервис для проверки смс перед отправкой и отправки
 */
class Sms extends Component
{

    public $login;
    public $password;
    public $srcSMS;
    private $sender;
    public $smsGateApiUrl;

    public function init()
    {
        $this->sender = new SmsSender($this->login, $this->password, $this->srcSMS, $this->smsGateApiUrl);
    }

    public function login(){
       echo $this->login;
    }

    /**
     * Отправляет SMS сообщение
     *
     * @param string $phone Телефон кому отправляем СМС должен быть в формате +<номер телефона в международном формате>
     * @param string $message Текст SMS сообщение
     * @param bool $isFreeSms - бесплатное ли SMS сообщение
     * @param User|null
     * - Пользователь, который платит, если это платная смс
     * @param string $type - Тип смс
     * @return bool
     * @throws \Exception
     */
    public function send($phone, $message)
    {
        \Yii::info('', __METHOD__);
        \Yii::info('Отправка SMS', __METHOD__);
        \Yii::info('Телефон: ' . $phone, __METHOD__);
        \Yii::info('Текст сообщения: ' . $message, __METHOD__);

        // индикатор успешности выполнения задачи. Если хоть на один номер ушла СМС, то задача считается успешной
        $successTask = true;

        // инициализация переменной под номер отправленного СМС на шлюзе
        $taskId = null;

        // Возможность указывать несколько телефонов через запятую
        $phones = self::preparePhone($phone);

        foreach ($phones as $phone) {
            try {
                \Yii::info('Проверки норм, пробуем отправить запрос', __METHOD__);
                $taskId = $this->sender->send($phone, $message);
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
        return $phones;
    }
}
