<?php

use App\Providers\AppServiceProvider;

$providers = [
    AppServiceProvider::class,
];

// Horizon: hanya register jika package terinstall (membutuhkan ext-pcntl yang hanya ada di Linux/Docker)
if (class_exists(\Laravel\Horizon\HorizonApplicationServiceProvider::class)) {
    $providers[] = \App\Providers\HorizonServiceProvider::class;
}

return $providers;
