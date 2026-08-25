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

        $setNasi = Category::where('project_id', $project->id)->where('name', 'Nasi')->first();

        if ($setNasi) {
            $setNasi->update(['name' => 'Set Nasi']);
        } else {
            $setNasi = Category::firstOrCreate(['project_id' => $project->id, 'name' => 'Set Nasi'], ['sort_order' => 0]);
        }

        $alaCarte = Category::firstOrCreate(['project_id' => $project->id, 'name' => 'Ala Carte'], ['sort_order' => 1]);

        $setNasiMenu = [
            [$setNasi->id, 'Nasi Sotong Goreng Tepung', 12.00],
            [$setNasi->id, 'Nasi Gulai Ikan Tongkol', 9.00],
            [$setNasi->id, 'Nasi Daging Berlengas', 9.00],
            [$setNasi->id, 'Nasi Ayam Cincang', 8.00],
            [$setNasi->id, 'Nasi Gulai Ayam', 7.00],
            [$setNasi->id, 'Nasi Keli Goreng', 7.00],
            [$setNasi->id, 'Nasi Berlauk Ayam Budget (Bungkus)', 6.00],
        ];

        $alaCarteMenu = [
            [$alaCarte->id, 'Sotong Goreng Tepung', 10.00],
            [$alaCarte->id, 'Daging Berlengas', 7.00],
            [$alaCarte->id, 'Ikan Tongkol', 7.00],
            [$alaCarte->id, 'Ayam Cincang', 6.00],
            [$alaCarte->id, 'Keli Goreng', 5.00],
            [$alaCarte->id, 'Ayam Gulai', 4.50],
            [$alaCarte->id, 'Nasi Putih', 2.00],
        ];

        foreach ($setNasiMenu as $i => [$categoryId, $name, $price]) {
            Product::updateOrCreate(
                ['project_id' => $project->id, 'name' => $name],
                ['category_id' => $categoryId, 'price' => $price, 'is_active' => true, 'sort_order' => $i]
            );
        }

        foreach ($alaCarteMenu as $i => [$categoryId, $name, $price]) {
            Product::updateOrCreate(
                ['project_id' => $project->id, 'name' => $name],
                ['category_id' => $categoryId, 'price' => $price, 'is_active' => true, 'sort_order' => $i]
            );
        }
    }
}
