<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    public function run()
    {
        // Ensure there's at least one user to own the teams
        $user = User::first() ?? User::factory()->create(['email' => 'admin@example.com']);

        // List of clients
        $clients = [
            'sheetz1',
            'sheetz',
            'ta',
            'qa2',
            'dev1',
            'rutters',
            'open',
            'parkland',
        ];

        foreach ($clients as $clientName) {
            // Create a team for each client
            $team = Team::create([
                'user_id' => $user->id,
                'name' => ucfirst($clientName) . ' Team',
                'personal_team' => false,
            ]);

            // Create the client and associate it with the team
            Client::create([
                'name' => $clientName,
                'team_id' => $team->id,
            ]);

            // Optionally, add the user to the team
            DB::table('team_user')->insert([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}