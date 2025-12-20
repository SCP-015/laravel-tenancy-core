<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Teknologi Informasi & Perangkat Lunak',
            'Keuangan & Perbankan',
            'Kesehatan & Farmasi',
            'Pendidikan',
            'Retail & E-commerce',
            'Manufaktur & Industri',
            'Konstruksi & Properti',
            'Pariwisata & Perhotelan',
            'Media & Hiburan',
            'Layanan Profesional',
            'Lainnya',
        ];

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'name' => $category,
                'slug' => Str::slug($category),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('company_categories')->insert($data);
    }
}
