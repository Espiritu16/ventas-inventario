<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Los seeders de dominio se agregan en su sprint: el usuario
        // administrador inicial en S-01-B, el cliente "público general" en
        // S-03-B y las series de comprobante en S-05-B.
    }
}
