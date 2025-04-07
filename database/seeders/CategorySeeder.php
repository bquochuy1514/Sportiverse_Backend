<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Sport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Danh sách các danh mục theo môn thể thao
        $sportCategories = [
            'Bóng đá' => [
                'Áo đấu' => [
                    'Áo đấu sân nhà',
                    'Áo đấu sân khách',
                    'Áo thủ môn'
                ],
                'Giày' => [
                    'Giày sân cỏ tự nhiên',
                    'Giày sân cỏ nhân tạo',
                    'Giày futsal'
                ],
                'Phụ kiện' => [
                    'Bóng',
                    'Găng tay thủ môn',
                    'Bảo vệ ống chân',
                    'Túi đựng giày'
                ]
            ],
            'Bóng rổ' => [
                'Áo đấu' => [
                    'Áo thi đấu',
                    'Áo tập luyện'
                ],
                'Giày' => [
                    'Giày thi đấu',
                    'Giày tập luyện'
                ],
                'Phụ kiện' => [
                    'Bóng rổ',
                    'Băng cổ tay',
                    'Băng đầu gối'
                ]
            ],
            'Bóng chuyền' => [
                'Áo đấu' => [
                    'Áo thi đấu nam',
                    'Áo thi đấu nữ',
                    'Áo tập luyện'
                ],
                'Giày' => [
                    'Giày thi đấu',
                    'Giày tập luyện'
                ],
                'Phụ kiện' => [
                    'Bóng chuyền',
                    'Bảo vệ đầu gối',
                    'Băng tay'
                ]
            ],
            'Cầu lông' => [
                'Vợt' => [
                    'Vợt tấn công',
                    'Vợt phòng thủ',
                    'Vợt toàn diện'
                ],
                'Giày' => [
                    'Giày thi đấu',
                    'Giày tập luyện'
                ],
                'Phụ kiện' => [
                    'Cầu lông',
                    'Túi đựng vợt',
                    'Dây cước',
                    'Quấn cán'
                ],
                'Trang phục' => [
                    'Áo thi đấu nam',
                    'Áo thi đấu nữ',
                    'Quần thi đấu'
                ]
            ],
            'Bơi lội' => [
                'Đồ bơi' => [
                    'Đồ bơi nam',
                    'Đồ bơi nữ',
                    'Đồ bơi trẻ em'
                ],
                'Phụ kiện' => [
                    'Kính bơi',
                    'Mũ bơi',
                    'Ống thở',
                    'Kẹp mũi',
                    'Tai nghe chống nước'
                ],
                'Thiết bị tập luyện' => [
                    'Phao tay',
                    'Ván tập bơi',
                    'Chân vịt'
                ]
            ],
            'Gym' => [
                'Trang phục' => [
                    'Áo tập nam',
                    'Áo tập nữ',
                    'Quần tập nam',
                    'Quần tập nữ'
                ],
                'Thiết bị' => [
                    'Tạ tay',
                    'Tạ đĩa',
                    'Dây kháng lực',
                    'Thảm tập',
                    'Máy tập đa năng'
                ],
                'Phụ kiện' => [
                    'Găng tay tập',
                    'Đai lưng',
                    'Bình nước',
                    'Khăn tập',
                    'Túi tập gym'
                ],
                'Thực phẩm bổ sung' => [
                    'Whey Protein',
                    'BCAA',
                    'Creatine',
                    'Pre-workout',
                    'Vitamin và khoáng chất'
                ]
            ]
        ];

        // Tạo các danh mục
        foreach ($sportCategories as $sportName => $categories) {
            // Sportname: Bóng đá, Bóng rổ, Bóng chuyền, Cầu lông, Bơi lội, Gym
            // Categories: Áo đấu, Giày, Phụ kiện, Vợt, Đồ bơi, Trang phục, Thiết bị, Thực phẩm bổ sung
            // Tìm sport theo tên
            $sport = Sport::where('name', $sportName)->first();
            if (!$sport) {
                $this->command->info("Không tìm thấy môn thể thao: {$sportName}");
                continue;
            }

            $this->command->info("Đang tạo danh mục cho môn {$sportName}");

            foreach ($categories as $parentName => $childCategories) {
                // ParentName: Áo đấu, Giày, Phụ kiện, Vợt, Đồ bơi, Trang phục, Thiết bị, Thực phẩm bổ sung
                // ChildCategories: Áo đấu sân nhà, Áo đấu sân khách, Áo thủ môn, Giày sân cỏ tự nhiên, Giày sân cỏ nhân tạo, Giày futsal
                // Tạo danh mục cha
                $parentSlug = Str::slug($sportName . '-' . $parentName);
                $parentCategory = Category::updateOrCreate(
                    ['slug' => $parentSlug],
                    [
                        'sport_id' => $sport->id,
                        'name' => $parentName,
                        'is_active' => true
                    ]
                );

                $this->command->info("  - Đã tạo danh mục cha: {$parentName}");

                // Tạo các danh mục con
                foreach ($childCategories as $childName) {
                    $childSlug = Str::slug($sportName . '-' . $parentName . '-' . $childName);
                    Category::updateOrCreate(
                        ['slug' => $childSlug],
                        [
                            'sport_id' => $sport->id,
                            'parent_id' => $parentCategory->id,
                            'name' => $childName,
                            'is_active' => true
                        ]
                    );
                }

                $this->command->info("    - Đã tạo " . count($childCategories) . " danh mục con");
            }
        }

        $this->command->info("Đã tạo xong tất cả danh mục!");
    }
}
