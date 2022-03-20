<?php

namespace App\Http\Controllers;

use App\Exceptions\SlotsException;
use App\Slots;
use App\User;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Throwable;

class BotManController extends Controller
{
    public BotMan $botman;

    /**
     * @return void
     */
    public function handle()
    {
        DriverManager::loadDriver(TelegramDriver::class);

        $this->botman = BotManFactory::create(config('slots.config'));

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
            Slots::freeSpin($bot);
        });

        $this->botman->hears('/spin', function ($bot) {
            Slots::spin($bot);
        });

        $this->botman->hears('/spin {bet}', function ($bot, $bet) {
            Slots::spin($bot, $bet);
        });

        $this->botman->listen();
    }

    /**
     * @param BotMan $bot
     * @return void
     */
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
        $user = User::query()->where('name', $username);

        if (!$user->exists()) {
            $bot->reply(__('slots.account.not_found'));
            return;
        }

        if (Hash::check($password, $user->first()->password)) {
            $bot->reply(__('slots.account.connected'));
            $user->update(['telegram_id' => $bot->getUser()->getId()]);
            return;
        }

        $bot->reply(__('slots.invalid_password'));
    }

    /**
     * @throws Throwable
     */
    public static function getUser(BotMan $bot): Builder
    {
        $user = User::query()->where('telegram_id', $bot->getUser()->getId());

        if (!$user->exists()) {
            throw new SlotsException($bot, __('slots.account.not_connected'));
        }

        return $user;
    }

    /**
     * @param BotMan $bot
     * @return void
     * @throws Throwable
     */
    public function disconnect(BotMan $bot)
    {
        $this->getUser($bot)->update(['telegram_id' => null]);
        $bot->reply(__('slots.account.disconnected'));
    }

    /**
     * @param BotMan $bot
     * @return void
     * @throws Throwable
     */
    public function check(BotMan $bot)
    {
        $bot->reply(__('slots.account.check_ok') . $this->getUser($bot)->first()->name);
    }

    /**
     * @param BotMan $bot
     * @return void
     * @throws Throwable
     */
    public function balance(BotMan $bot)
    {
        $bot->reply('Баланс на сайте: ' . $this->getUser($bot)->first()->user_balance);
    }

    /**
     * @param BotMan $bot
     * @param string $username
     * @return void
     * @throws Throwable
     */
    public function getUserBalance(BotMan $bot, string $username)
    {
        $user = User::query()->where('name', $username);

        if (!$user->exists()) {
            throw new SlotsException($bot, __('slots.account.not_found'));
        }

        $bot->reply(__('slots.user_balance') . $user->first()->user_balance);
    }
}
