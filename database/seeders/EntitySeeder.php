<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entity;

class EntitySeeder extends Seeder
{
    public function run(): void
    {
        $entities = [
            [
                'name' => 'Ministry of Health',
                'name_ar' => 'وزارة الصحة',
                'email' => 'health@gov.jo',
                'phone' => '+962-6-5678901',
                'description' => 'Ministry responsible for public health services',
                'description_ar' => 'الوزارة المسؤولة عن خدمات الصحة العامة',
                'type' => 'ministry',
                'is_active' => true,
            ],
            [
                'name' => 'Ministry of Education',
                'name_ar' => 'وزارة التربية والتعليم',
                'email' => 'education@gov.jo',
                'phone' => '+962-6-5678902',
                'description' => 'Ministry responsible for education',
                'description_ar' => 'الوزارة المسؤولة عن التعليم',
                'type' => 'ministry',
                'is_active' => true,
            ],
            [
                'name' => 'Water Authority',
                'name_ar' => 'سلطة المياه',
                'email' => 'water@gov.jo',
                'phone' => '+962-6-5678903',
                'description' => 'Government authority for water management',
                'description_ar' => 'السلطة الحكومية لإدارة المياه',
                'type' => 'government_party',
                'is_active' => true,
            ],
        ];

        foreach ($entities as $entity) {
            Entity::create($entity);
        }
    }
}
