<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => 'admin123',
            'role' => 'admin',
            'avatar' => url('/storage/avatars/admin.jpg'),
            'phone' => '0342637682',
            'address' => '12B Trương Hán Siêu, Nha Trang, Khánh Hòa'
        ]);

        User::factory()->create([
            'name' => 'Bùi Quốc Huy',
            'email' => 'banavip123nt@gmail.com',
            'password' => '123456',
            'role' => 'customer',
            'avatar' => url('/storage/avatars/default.jpg'),
            'phone' => '0342637682',
            'address' => '12B Trương Hán Siêu, Nha Trang, Khánh Hòa'
        ]);
        
        $this->call([
            SportSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
