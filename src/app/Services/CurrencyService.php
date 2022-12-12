<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

class CurrencyService
{
    protected Carbon $date;

    protected CbrCurlService $cbrCurlService;

    protected string $codeBaseCurrency;

    protected string $codeQuotedCurrency;

    public function __construct(CbrCurlService $cbrCurlService)
    {
        $this->cbrCurlService = $cbrCurlService;
    }

    public function index(Request $request): array
    {
        // Параметры.
        $this->date = $request->get('date') ? Carbon::create($request->get('date')) : Carbon::now();
        $this->codeQuotedCurrency = $request->get('code_quoted_currency');
        $this->codeBaseCurrency = $request->get('code_base_currency');

        $cursDate = $this->getValueCursOnDate();

        // Курс предыдущего дня.
        $cursDateYesterday = $this->getValueCursOnDate($this->date->subDay());

        return [

            // Значение курса.
            'curs' => sprintf("%01.4f", $cursDate),

            // Разница с предыдущим днем.
            'diff' => sprintf("%01.4f", $cursDate - $cursDateYesterday)
        ];
    }

    private function getValueCursOnDate(Carbon $date = null): float
    {
        $date = $date ?? $this->date;

        $parse = $this->parseResponseXML($date->format('Y-m-d'));

        // Базовая валюта.
        $baseCurrency = data_get($parse, $this->codeBaseCurrency);

        // Котируемая валюта.
        $quotedCurrency = data_get($parse, $this->codeQuotedCurrency);

        return (int)$quotedCurrency->Vcurs  / (int)$baseCurrency->Vcurs;
    }

    private function parseResponseXML(string $date): array
    {
        // Курсы валют от cbr.
        $listCursOnDateXML = $this->getResponseXML($date);

        $listCursOnDateXML = simplexml_load_string($listCursOnDateXML);

        // Поиск элементов ValuteCursOnDate.
        $listCursOnDate = $listCursOnDateXML->xpath("//ValuteCursOnDate");

        // Форматирование полученного результата.
        foreach ($listCursOnDate as $key => $value) {
            $listCursOnDate[strval($value->Vcode)] = $value;
            unset($listCursOnDate[$key]);
        }

        return $listCursOnDate;
    }

    private function getResponseXML(string $date): string
    {
        if (!app('redis')->exists($date)) {
            // Отправка запроса на получение курсов валют от cbr.
            // Сохранение результата.
            app('redis')->set($date, $this->cbrCurlService->getCurse($date));
        }

        return app('redis')->get($date);
    }
}
