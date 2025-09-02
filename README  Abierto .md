Base project using 
- laravel jetstream with livewire and tailwind 
- simple CRUD with notes
- team install 

added debug bar

composer require barryvdh/laravel-debugbar --dev

should work but can add this to providers:
Barryvdh\Debugbar\ServiceProvider::class,

to add if we want to customize
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"


Call to device info lambda stores in redis cache.

Configured to run in Docker with 3 images using sail


added crontab initiated event to load cache in docker need to run it by hand from tinker

> $command = new \App\Console\Commands\FetchDeviceData;
= App\Console\Commands\FetchDeviceData {#5776}

> $command->handle();

Need to fix:
- screen first needs to check cache if empty for all clients needs to call command to load it
- large device issue display
