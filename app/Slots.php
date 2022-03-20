<?php

namespace App;

use App\Http\Controllers\BotManController;
use BotMan\BotMan\BotMan;
use Illuminate\Support\Facades\Log;
use Throwable;

class Slots
{
    /**
     * @param BotMan $bot
     * @return void
     */
    public static function freeSpin(BotMan $bot)
    {
        Log::info('User ' . $bot->getUser()->getUsername() . ' initiated free spin');

        $slots = self::generateNumbers();

        $message = collect($slots)->implode('');

        if ($slots[0] === $slots[1] && $slots[1] === $slots[2]) {
            $message .= __('slots.response.triple');
        } else if ($slots[0] === $slots[1] || $slots[1] === $slots[2]) {
            $message .= __('slots.response.double');
        } else {
            $message .= __('slots.response.fail');
        }

        $bot->reply($message);
    }

    /**
     * @param BotMan $bot
     * @param $bet
     * @return void
     * @throws Throwable
     */
    public static function spin(BotMan $bot, $bet = null): void
    {
        $user = BotManController::getUser($bot);

        if (is_null($bet)) {
            $bet = config('slots.basic_bet');
        } else {
            $balance = $user->first()->user_balance;

            if ($bet > $balance) {
                $bot->reply(__('slots.not_enough') . __('slots.currency') . '.');
                return;
            }

            if ($bet < config('slots.basic_bet')) {
                $bot->reply(config('slots.min_bet') . ' - ' . config('slots.basic_bet') . __('slots.currency') . '.');
                return;
            }
        }

        $user->decrement('user_balance', $bet);
        $sitename = $user->first()->name;
        Log::info('User ' . $sitename . ' initiated slot spin with bet ' . $bet);

        $slots = self::generateNumbers();

        $message = collect($slots)->implode('');

        if ($slots[0] === $slots[1] && $slots[1] === $slots[2]) {
            $prize = $bet * config('slots.multipliers.triple');
        } else if ($slots[0] === $slots[1] || $slots[1] === $slots[2]) {
            $prize = $bet * config('slots.multipliers.double');
        } else {
            $message .= __('slots.response.fail');
            $bot->reply($message);
            return;
        }

        $message .= __('slots.you_won') . $prize . ' ' . __('slots.currency') . '!';
        $user->increment('user_balance', $prize);
        Log::info('User ' . $sitename . ' won ' . $prize);

        $bot->reply($message);
    }

    /**
     * @return array
     */
    public static function generateNumbers(): array
    {
        return [
            self::convertToEmoji(rand(1, 5)),
            self::convertToEmoji(rand(1, 5)),
            self::convertToEmoji(rand(1, 5)),
        ];
    }

    /**
     * @param int $number
     * @return string
     */
    public static function convertToEmoji(int $number): string
    {
        switch ($number) {
            case 1:
                return config('slots.emoji.1');
            case 2:
                return config('slots.emoji.2');
            case 3:
                return config('slots.emoji.3');
            case 4:
                return config('slots.emoji.4');
            case 5:
                return config('slots.emoji.5');
            default:
                return config('slots.emoji.default');
        }
    }
}