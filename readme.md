# Отправка SMS-сообщений
## Установка
В ``composer.json`` добавляем  
````
"require": {
    "bubogumy/yii2-sms": "dev-master"
}
````
В консоль пишем для установки: ``composer require bubogumy/yii2-sms``  

Подключаем наше приложение
````
'sms' => [
            'class' => 'bubogumy\Sms',
            'login' => 'login_test',
            'password' => 'password_test',
            'srcSMS' => 'src_test',
            'smsGateApiUrl' => ''
        ]
````
Для отправки сообщений используем
````
use bubogumy\Sms;  
Yii::$app->sms->send($phone = '+78005553535', $message='Проще позвонить');
````