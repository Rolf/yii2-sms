<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 11.04.16
 * Time: 15:13
 */

namespace app\services\common\sms;

use app\helpers\Format;
use Yii;

class SmsSender
{
    private $curl = null;
    private $postFields;
    private static $instance = null;

    /**
     * Защищаем от создания через new Singleton
     */
    private function __construct()
    {
        /* ... @return SmsSender */
    }

    /**
     * Защищаем от создания через клонирование
     */
    private function __clone()
    {
        /* ... @return SmsSender */
    }

    /*
     * Защищаем от создания через unserialize
     */
    private function __wakeup()
    {
        /* ... @return SmsSender */
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->postFields = array(
                'Login' => Yii::$app->params['smsGateLogin'],
                'Password' => Yii::$app->params['smsGatePassword'],
                'SourceAddress' => Yii::$app->params['smsGateSender'],
            );
            self::$instance->curl = curl_init();
            curl_setopt(self::$instance->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(self::$instance->curl, CURLOPT_POST, true);
            curl_setopt(self::$instance->curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt(self::$instance->curl, CURLOPT_SSL_VERIFYHOST, 0);
        }
        return self::$instance;
    }

    /**
     * Отправить сообщение и вернуть результат (id задачи в шлюзе)
     *
     * @param string $phone номер телефона
     * @param string $message сообщение
     * @return int id задачи в шлюзе
     */
    public static function send($phone, $message)
    {
        $sender = self::getInstance();

        $sender->postFields = array_merge($sender->postFields, [
            'Data' => $message,
            'DestinationAddress' => $phone,
        ]);

        $dataCurl = http_build_query($sender->postFields);
        curl_setopt($sender->curl, CURLOPT_URL, Yii::$app->params['smsGateApiUrl']  . '/Send');
        curl_setopt($sender->curl, CURLOPT_POSTFIELDS, $dataCurl);

        $answerJson = curl_exec($sender->curl);

        \Yii::info('Шлюз принял смс, id: ' . strval($answerJson), __CLASS__);

        $answerArray = json_decode($answerJson, true);
        return $answerArray[0];
    }


    public static function check($messageId)
    {
        $sender = self::getInstance();

        $sender->postFields = array_merge($sender->postFields, [
            'messageId' => $messageId,
        ]);

        $dataCurl = http_build_query($sender->postFields);
        curl_setopt($sender->curl, CURLOPT_URL, Yii::$app->params['smsGateApiUrl']  . '/State');
        curl_setopt($sender->curl, CURLOPT_POSTFIELDS, $dataCurl);
        $answerJson = curl_exec($sender->curl);
        $answerArray = json_decode($answerJson, true);

        \Yii::info('Проверка по ' . $messageId . ' : ' . $answerArray['State'], __CLASS__);
        return $answerArray['State'];
    }
}
