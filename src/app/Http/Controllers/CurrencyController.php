<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CurrencyController extends Controller
{
    private CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * @throws \Exception
     */
    public function index(Request $request): Response
    {
        $result = $this->currencyService->index($request);

        return response([
            'data' => $result
        ], Response::HTTP_OK);
    }
}
