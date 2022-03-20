<?php

namespace App\Exceptions;

use BotMan\BotMan\BotMan;
use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

class SlotsException extends Exception
{
    public function __construct(BotMan $bot, $message = "", $code = 200, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $bot->reply($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([]);
    }
}
