<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Machine;

class MachineSeeder extends Seeder
{
   
    public function run(): void
    {
        foreach (['Pulverizador', 'Plantadeira', 'Trator', 'Colheitadeira',] as $name) {
            Machine::firstOrCreate(['name' => $name]);
        }
    }
}
