<?php

return [
    'commands' => [
        'menu' => 'открывает меню',
        'ping' => 'pong',
        'freespin' => 'бесплатная рулетка',
        'connect' => 'связка с сайтом',
        'auth_only' => 'Для выполнения следущих команд, ваш аккаунт должен быть привязан к сайту с помощью команды выше. Делайте это исключительно в лс бота!',
        'disconnect' => 'отвязка от сайта',
        'check' => 'проверка подвязан ли аккаунт к сайту',
        'balance' => 'показывает баланс с сайта',
        'spin' => 'рулетка за 100 хекселей',
        'custom_spin' => 'рулетка со ставкой',
    ],
    'fill_data' => 'Вы забыли ввести после команды ник и пароль.',
    'invalid_password' => 'Неверный пароль.',
    'user_balance' => 'Баланс пользователя: ',
    'min_bet' => 'Минимальная ставка',
    'not_enough' => 'Недостаточно ',
    'currency' => 'хекселей',
    'you_won' => "\nТы выиграл ",
    'response' => [
        'fail' => "\nПовезёт в следущий раз!",
        'double' => "\nНеплохо!",
        'triple' => "\nПовезло! 🎆",
    ],
    'account' => [
        'connected' => 'Аккаунт был успешно привязан.',
        'not_connected' => "Ваш аккаунт не привязан.\nВведите команду /connect ник пароль.",
        'disconnected' => "Ваш аккаунт был успешно отвязан.\n",
        'not_found' => 'Аккаунт не найден.',
        'check_ok' => 'Аккаунт привязан, ваш ник:',
    ],
];