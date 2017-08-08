<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 11.04.16
 * Time: 15:13
 */

namespace bubogumy;

use Yii;

class SmsSender
{
    private $curl = null;
    private $postFields;
    private $smsGateApiUrl;

    public function __construct($login, $password, $srcSMS, $smsGateApiUrl)
    {
            $this->postFields = array(
                'Login' => $login,
                'Password' => $password,
                'SourceAddress' => $srcSMS,
            );
            $this->smsGateApiUrl = $smsGateApiUrl;
            $this->curl = curl_init();
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
    }

    /**
     * Отправить сообщение и вернуть результат (id задачи в шлюзе)
     *
     * @param string $phone номер телефона
     * @param string $message сообщение
     * @return int id задачи в шлюзе
     */
    public function send($phone, $message)
    {
        $this->postFields = array_merge($this->postFields, [
            'Data' => $message,
            'DestinationAddress' => $phone,
        ]);

        $dataCurl = http_build_query($this->postFields);
        curl_setopt($this->curl, CURLOPT_URL, $this->smsGateApiUrl  . '/Send');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $dataCurl);

        $answerJson = curl_exec($this->curl);

        \Yii::info('Шлюз принял смс, id: ' . strval($answerJson), __CLASS__);

        $answerArray = json_decode($answerJson, true);
        return $answerArray[0];
    }


    public function check($messageId)
    {
        $this->postFields = array_merge($this->postFields, [
            'messageId' => $messageId,
        ]);

        $dataCurl = http_build_query($this->postFields);
        curl_setopt($this->curl, CURLOPT_URL, Yii::$app->params['smsGateApiUrl']  . '/State');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $dataCurl);
        $answerJson = curl_exec($this->curl);
        $answerArray = json_decode($answerJson, true);

        \Yii::info('Проверка по ' . $messageId . ' : ' . $answerArray['State'], __CLASS__);
        return $answerArray['State'];
    }
}
