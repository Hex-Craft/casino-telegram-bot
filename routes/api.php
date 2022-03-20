<?php

use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/handle', 'BotManController@handle');