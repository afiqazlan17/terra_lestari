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

        $nasi = Category::firstOrCreate(['project_id' => $project->id, 'name' => 'Nasi'], ['sort_order' => 0]);
        $lauk = Category::firstOrCreate(['project_id' => $project->id, 'name' => 'Lauk'], ['sort_order' => 1]);
        $minuman = Category::firstOrCreate(['project_id' => $project->id, 'name' => 'Minuman'], ['sort_order' => 2]);

        $menu = [
            [$nasi->id, 'Nasi Kerabu', 8.00],
            [$nasi->id, 'Nasi Dagang', 7.00],
            [$lauk->id, 'Ayam Percik', 6.00],
            [$lauk->id, 'Solok Lada', 3.00],
            [$minuman->id, 'Air Sirap Bandung', 2.50],
            [$minuman->id, 'Teh Tarik', 2.50],
        ];

        foreach ($menu as $i => [$categoryId, $name, $price]) {
            Product::firstOrCreate(
                ['project_id' => $project->id, 'name' => $name],
                ['category_id' => $categoryId, 'price' => $price, 'is_active' => true, 'sort_order' => $i]
            );
        }
    }
}
