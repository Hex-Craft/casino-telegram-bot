<?php

namespace App\Http\Controllers;

use App\User;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class BotManController extends Controller
{
    public BotMan $botman;

    public function handle()
    {
        $config = [
            'telegram' => [
                'token' => env('TELEGRAM_BOT_TOKEN'),
            ],
        ];

        DriverManager::loadDriver(TelegramDriver::class);

        $this->botman = BotManFactory::create($config);

        $this->botman->hears('/start', function ($bot) {
            $this->showMenu($bot);
        });

        $this->botman->hears('/menu', function ($bot) {
            $this->showMenu($bot);
        });

        $this->botman->hears('/ping', function ($bot) {
            $bot->reply("pong!");
        });

        $this->botman->hears('/connect', function ($bot) {
            $bot->reply(__('slots.fill_data'));
        });

        $this->botman->hears('/disconnect', function ($bot) {
            $this->disconnect($bot);
        });

        $this->botman->hears('/connect {username} {password}', function ($bot, $username, $password) {
            $this->connect($bot, $username, $password);
        });

        $this->botman->hears('/check', function ($bot) {
            $this->check($bot);
        });

        $this->botman->hears('/balance', function ($bot) {
            $this->balance($bot);
        });

        $this->botman->hears('/freespin', function ($bot) {
            $this->spin($bot, false, true);
        });

        $this->botman->hears('/debugspin', function ($bot) {
            $this->spin($bot, true, true);
        });

        $this->botman->hears('/spin', function ($bot) {
            $this->spin($bot);
        });

        $this->botman->hears('/spin {bet}', function ($bot, $bet) {
            $this->spin($bot, false, false, $bet);
        });

        $this->botman->listen();
    }

    public function showMenu(BotMan $bot): void
    {
        $message = '/menu - ' . __('slots.commands.menu') .  ";\n";
        $message .= '/ping - ' . __('slots.commands.ping') .  ";\n";
        $message .= '/freespin - ' . __('slots.commands.freespin') . ";\n";
        $message .= '/connect {username} {password} - ' . __('slots.commands.connect') .  ";\n";
        $message .= "\n" . __('slots.commands.auth_only') .  "\n\n";
        $message .= '/disconnect - ' . __('slots.commands.disconnect') .  ";\n";
        $message .= '/check - ' . __('slots.commands.check') .  ";\n";
        $message .= '/balance - ' . __('slots.commands.balance') .  ";\n";
        $message .= '/spin - ' . __('slots.commands.spin') .  ";\n";
        $message .= '/spin {bet} - ' . __('slots.commands.custom_spin') .  ";\n";

        $bot->reply($message);
    }

    public function connect(BotMan $bot, string $username, string $password): void
    {
        $telegramUser = $bot->getUser();
        $telegramId = $telegramUser->getId();

        $user = User::query()->where('name', $username);

        if (!$user->exists()) {
            $bot->reply(__('slots.account.not_found'));
            return;
        }

        if (Hash::check($password, $user->first()->password)) {
            $bot->reply(__('slots.account.connected'));
            $user->update(['telegram_id' => $telegramId]);
            return;
        }

        $bot->reply(__('slots.invalid_password'));
    }

    public function getUser(BotMan $bot): Builder
    {
        $telegramUser = $bot->getUser();
        $telegramId = $telegramUser->getId();

        return User::query()->where('telegram_id', $telegramId);
    }

    public function disconnect(BotMan $bot)
    {
        $user = $this->getUser($bot);

        if (!$user->exists()) {
            $bot->reply(__('slots.account.not_connected'));
            return;
        }

        $user->update(['telegram_id' => null]);
        $bot->reply(__('slots.account.disconnected'));
    }

    public function check(BotMan $bot)
    {
        $user = $this->getUser($bot);

        if (!$user->exists()) {
            $bot->reply(__('slots.account.not_connected'));
            return;
        }

        $bot->reply(__('slots.account.check_ok') . $user->first()->name);
    }

    public function balance(BotMan $bot)
    {
        $user = $this->getUser($bot);

        if (!$user->exists()) {
            $bot->reply(__('slots.account.not_connected'));
            return;
        }

        $bot->reply('Баланс на сайте: ' . $user->first()->user_balance);
    }

    public function getUserBalance(BotMan $bot, string $username)
    {
        $user = User::query()->where('name', $username);

        if (!$user->exists()) {
            $bot->reply(__('slots.account.not_found'));
            return;
        }

        $bot->reply(__('slots.user_balance') . $user->first()->user_balance);
    }

    public function spin(BotMan $bot, $debug = false, $free = false, $bet = null)
    {
        if (!$free) {
            $user = $this->getUser($bot);

            if (!$user->exists()) {
                $bot->reply(__('slots.account.not_connected'));
                return;
            }

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
        } else {
            $user = $bot->getUser();
            $username = $user->getUsername();
            Log::info('User ' . $username . ' initiated free spin');
        }

        $first = rand(1, 5);
        $second = rand(1, 5);
        $third = rand(1, 5);

        $message = $this->convertToEmoji($first);
        $message .= $this->convertToEmoji($second);
        $message .= $this->convertToEmoji($third);

        if ($debug) {
            $message .= $first . $second . $third;
        }

        if ($free) {
            if ($first === $second && $second === $third) {
                $message .= __('slots.response.triple');
            } else if ($first === $second || $second === $third) {
                $message .= __('slots.response.double');
            } else {
                $message .= __('slots.response.fail');
            }
        } else {
            if ($first === $second && $second === $third) {
                $prize = $bet * config('slots.multipliers.triple');
            } else if ($first === $second || $second === $third) {
                $prize = $bet * config('slots.multipliers.double');
            } else {
                $message .= __('slots.response.fail');
                $bot->reply($message);
                return;
            }

            $message .= __('slots.you_won') . $prize . ' ' . __('slots.currency') . '!';
            $user->increment('user_balance', $prize);
            Log::info('User ' . $sitename . ' won ' . $prize);
        }
        $bot->reply($message);
    }

    public function convertToEmoji(int $number): string
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
