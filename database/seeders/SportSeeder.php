<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Danh sách các môn thể thao
        $sports = [
            [
                'name' => 'Bóng đá',
                'description' => 'Bóng đá là môn thể thao đồng đội, sử dụng quả bóng hình cầu. Đây là môn thể thao phổ biến nhất thế giới với hơn 250 triệu người chơi tại hơn 200 quốc gia.',
                'icon' => 'sports/soccer.jpg',
            ],
            [
                'name' => 'Bóng rổ',
                'description' => 'Bóng rổ là môn thể thao đồng đội, trong đó hai đội, mỗi đội có năm cầu thủ, đối đầu trên một sân chữ nhật, thi đấu ghi điểm bằng cách ném bóng vào rổ của đối phương.',
                'icon' => 'sports/basketball.jpg',
            ],
            [
                'name' => 'Cầu lông',
                'description' => 'Cầu lông là môn thể thao dùng vợt, được chơi bởi hai đối thủ (đánh đơn) hoặc hai cặp đối thủ (đánh đôi), trên một sân có lưới giăng ở giữa.',
                'icon' => 'sports/badminton.jpg',
            ],
            [
                'name' => 'Gym',
                'description' => 'Gym là hoạt động tập luyện thể dục thể hình, sử dụng các thiết bị tập luyện để phát triển sức mạnh, sức bền và vóc dáng cơ thể.',
                'icon' => 'sports/gym.jpg',
            ],
            [
                'name' => 'Bơi lội',
                'description' => 'Bơi lội là hoạt động di chuyển qua nước bằng cách sử dụng tay, chân và cơ thể. Đây là môn thể thao có lợi cho sức khỏe và phát triển thể chất toàn diện.',
                'icon' => 'sports/swimming.jpg',
            ],
            [
                'name' => 'Bóng chuyền',
                'description' => 'Bóng chuyền là môn thể thao đồng đội, trong đó hai đội, mỗi đội có sáu người chơi, cách nhau bởi một tấm lưới cao, thi đấu trên một sân chữ nhật.',
                'icon' => 'sports/volleyball.jpg',
            ]
        ];

        // Thêm dữ liệu vào database
        foreach ($sports as $sportData) {
            // Tạo slug từ tên
            $sportData['slug'] = Str::slug($sportData['name']);
            
            // Thêm trạng thái active
            $sportData['is_active'] = true;
            
            Sport::create($sportData);
        }
    }
}
