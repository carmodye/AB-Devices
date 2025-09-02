<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    public function run()
    {
        Client::create(['name' => 'sheetz1']);
        Client::create(['name' => 'sheetz']);
        Client::create(['name' => 'ta']);
        Client::create(['name' => 'qa1']);
        Client::create(['name' => 'dev1']);
    }
}