<?php
namespace AppBundle\Service;

class TelegramService
{
    const API_HOST = 'https://api.telegram.org/bot';

    private $botToken;

    public function __construct($botToken)
    {
        $this->botToken = $botToken;
    }

    public function getMe()
    {
        return $this->api('getMe');
    }

    public function answerInlineQuery($queryId, $results)
    {
        return $this->api('answerInlineQuery', [
            'inline_query_id' => $queryId,
            'results' => json_encode($results)
        ]);
    }

    public function sendMessage($chatId, $text, $loud = false)
    {
        return $this->api('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'disable_notification' => ($loud ? false : true)
        ]);
    }

    private function api($method, $params = [])
    {
        $result = false;
        $url = self::API_HOST . $this->botToken .'/'. $method;
        try{
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if(is_array($params)) {
                $encodedParams = http_build_query($params);
            }else{
                $encodedParams = $params;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedParams);
//            file_put_contents('telegramApi.log', date('Y-m-d H:m:i') . ' QUERY: ' . $url .PHP_EOL. print_r($encodedParams, true). PHP_EOL, FILE_APPEND);
            $response = curl_exec($ch);
//            file_put_contents('telegramApi.log', date('Y-m-d H:m:i') . ' RESPONSE: ' . $response . PHP_EOL, FILE_APPEND);
            $response = json_decode($response);
            if($response->ok == true) {
                $result = $response->result;
            };
        }catch (\Exception $e){
            echo $e->getMessage().PHP_EOL;
        }
        return $result;
    }
}