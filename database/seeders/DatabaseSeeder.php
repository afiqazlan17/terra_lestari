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
use Illuminate\Support\Str;

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

        $seedAccounts = [
            ['email' => 'benn_mdshah@outlook.com', 'name' => 'Ben', 'role' => User::ROLE_OWNER],
            ['email' => 'afiq@kretiv.co', 'name' => 'Afiq', 'role' => User::ROLE_SUPERUSER],
            ['email' => 'amirul@kretiv.co', 'name' => 'Amirul', 'role' => User::ROLE_SUPERUSER],
        ];

        foreach ($seedAccounts as $account) {
            $existing = User::where('email', $account['email'])->first();

            if ($existing) {
                continue;
            }

            $password = Str::password(14, symbols: false);

            User::create([
                'email' => $account['email'],
                'name' => $account['name'],
                'password' => Hash::make($password),
                'role' => $account['role'],
                'project_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $this->command?->warn("Akaun dicipta: {$account['email']} / password sementara: {$password} (tukar serta-merta guna Profile)");
        }

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
