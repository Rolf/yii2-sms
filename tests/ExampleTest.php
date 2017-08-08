<?php
class SmsTest extends \Codeception\Test\Unit
{
    public function testSend()
    {
        $sms = Yii::createObject(\bubogumy\Sms::class, [
            'login' => 'login_test',
            'password' => 'password_test',
            'srcSMS' => 'src_test',
        ]);
        $sms->send('+777777777', 'message');
    }
}