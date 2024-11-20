<?php

namespace App\Http\Library\FireAlarmPanel;

use App\Http\Logic\ResponseLogic;

class HuiXiao
{
    public $header = '7c7c';
    public function send($message,$return = false)
    {
        $host = 'tcp://' . config('services.fire_alarm_panel.tcp_ip');
//        print_r($host);die;
        $port = config('services.fire_alarm_panel.tcp_port');; // 替换为你的端口号
//        $message = "7c7c103B240B0Aabce2131"; // 替换为你想发送的消息
//        $hexMessage = unpack('H*', $message)[1];
        // 创建一个socket连接
        $socket = fsockopen($host, $port, $errno, $errstr, 2);

        if (!$socket) {
            ResponseLogic::setMsg("Unable to connect: $errstr ($errno)");
            return false;
        }

        // 发送消息
        fwrite($socket, hex2bin($message));
        $response = true;
        if($return){
            $response = bin2hex(fread($socket, 1024));
        }
        // 关闭连接
        fclose($socket);

        return $response;
    }

    public function setTime($deviceId)
    {
        $year = substr(date('Y'), 2, 2);
        $month = date('m');
        $day = date('d');
        $hour = date('H');
        $minute = date('i');
        $second = date('s');

        $yearHex = str_pad(dechex($year), 2, '0', STR_PAD_LEFT);
        $monthHex = str_pad(dechex($month), 2, '0', STR_PAD_LEFT);
        $dayHex = str_pad(dechex($day), 2, '0', STR_PAD_LEFT);
        $hourHex = str_pad(dechex($hour), 2, '0', STR_PAD_LEFT);
        $minuteHex = str_pad(dechex($minute), 2, '0', STR_PAD_LEFT);
        $secondHex = str_pad(dechex($second), 2, '0', STR_PAD_LEFT);

        $hex = '8e8e8e' . $yearHex . $monthHex . $dayHex . $hourHex . $minuteHex . $secondHex . '7f';

        $message = $this->header . $deviceId . $hex;
//        print_r($message);die;
        return $this->send($message);
    }

    public function setIpPort($deviceId,$ip,$port)
    {
        $ipArr = explode('.',$ip);
        if(count($ipArr) != 4){
            ResponseLogic::setMsg("ip格式有误");
            return false;
        }

        $ipHex = '';
        foreach ($ipArr as $value){
            $ipHex .= str_pad(dechex($value), 3, '0', STR_PAD_LEFT);
        }

        $portHex = str_pad(dechex($port), 5, '0', STR_PAD_LEFT);

        $hex = '8c8c01' . $ipHex . $portHex . '7f';

        $message = $this->header . $deviceId . $hex;
    }

    public function sendCommand($deviceId,$command)
    {
        $message = $this->header . $deviceId . $command;

        return $this->send($message);
    }
}
