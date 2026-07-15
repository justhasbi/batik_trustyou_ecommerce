<?php

use Illuminate\Support\Facades\Route;

Route::view('/{any?}', 'app')->where('any', '^(?!admin|api|livewire|storage|build|up).*$');
