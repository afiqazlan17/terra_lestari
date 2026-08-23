<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['slug' => 'terra-lestari'],
            ['name' => 'Terra Lestari']
        );

        $project = Project::firstOrCreate(
            ['slug' => 'sajian-baginda'],
            [
                'company_id' => $company->id,
                'name' => 'Sajian Baginda',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'ben@sajianbaginda.com'],
            [
                'name' => 'Ben',
                'password' => Hash::make('password'),
                'role' => User::ROLE_OWNER,
                'project_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'afiq@kretiv.co'],
            [
                'name' => 'Afiq',
                'password' => Hash::make('TerraLestari-Afiq-2026'),
                'role' => User::ROLE_SUPERUSER,
                'project_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'amirul@kretiv.co'],
            [
                'name' => 'Amirul',
                'password' => Hash::make('TerraLestari-Amirul-2026'),
                'role' => User::ROLE_SUPERUSER,
                'project_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $nasi = Category::firstOrCreate(['project_id' => $project->id, 'name' => 'Nasi'], ['sort_order' => 0]);

        $menu = [
            [$nasi->id, 'Nasi Gulai Ikan Tongkol', 9.00],
            [$nasi->id, 'Nasi Daging Berlengas', 9.00],
            [$nasi->id, 'Nasi Ayam Cincang', 8.00],
            [$nasi->id, 'Nasi Gulai Ayam', 7.00],
            [$nasi->id, 'Nasi Keli Goreng', 7.00],
        ];

        foreach ($menu as $i => [$categoryId, $name, $price]) {
            Product::firstOrCreate(
                ['project_id' => $project->id, 'name' => $name],
                ['category_id' => $categoryId, 'price' => $price, 'is_active' => true, 'sort_order' => $i]
            );
        }
    }
}
