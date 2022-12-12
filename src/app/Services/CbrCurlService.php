<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class CbrCurlService
{
    private string $url;

    private string $body;

    private array $headers;

    public function __construct()
    {
        $this->url = 'https://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx';
        $this->body = '<?xml version="1.0" encoding="utf-8"?>
            <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
              <soap12:Body>
                <GetCursOnDate xmlns="http://web.cbr.ru/">
                  <On_date></On_date>
                </GetCursOnDate>
              </soap12:Body>
            </soap12:Envelope>';
        $this->headers = [
            'Content-Type: application/soap+xml;charset=utf-8',
            'SOAPAction: http://web.cbr.ru/GetCursOnDate'
        ];
    }

    /**
     * Отправка запроса.
     *
     * @throws Exception
     */
    public function getCurse(string $date = null): string
    {
        // Добавление даты в тело запроса.
        $this->addDateInRequestBody($date);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        $result = curl_exec($ch);

        // Статус ответа.
        $statusCode = data_get(curl_getinfo($ch), 'http_code');

        curl_close($ch);

        // Проверка статуса ответа.
        if ($statusCode !== Response::HTTP_OK) {
            throw new Exception("Error. Status curl response: $statusCode", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $result;
    }

    /**
     * Добавление даты в тело запроса.
     *
     * @param string|null $date
     * @return void
     */
    private function addDateInRequestBody(string $date = null): void
    {
        $this->body = str_replace('<On_date></On_date>', "<On_date>$date</On_date>", $this->body);
    }
}
