<?php

namespace Database\Seeders;

use App\Models\Community;
use Illuminate\Database\Seeder;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        $communities = [
            // Hindu - 21 communities
            ['religion' => 'Hindu', 'community_name' => 'Bunts', 'sub_communities' => json_encode(['Bunt', 'Jain Bunt', 'Shetty', 'Nadoja']), 'is_active' => true, 'sort_order' => 1],
            ['religion' => 'Hindu', 'community_name' => 'Billava', 'sub_communities' => json_encode(['Poojary', 'Billava']), 'is_active' => true, 'sort_order' => 2],
            ['religion' => 'Hindu', 'community_name' => 'Mogaveera', 'sub_communities' => json_encode(['Kharvi', 'Mogaveera']), 'is_active' => true, 'sort_order' => 3],
            ['religion' => 'Hindu', 'community_name' => 'GSB - Goud Saraswat Brahmin', 'sub_communities' => json_encode(['Shenoy', 'Pai', 'Kamath', 'Nayak', 'Baliga']), 'is_active' => true, 'sort_order' => 4],
            ['religion' => 'Hindu', 'community_name' => 'Havyaka Brahmin', 'sub_communities' => json_encode(['Havyaka']), 'is_active' => true, 'sort_order' => 5],
            ['religion' => 'Hindu', 'community_name' => 'Shivalli Brahmin', 'sub_communities' => json_encode(['Madhva Brahmin', 'Shivalli']), 'is_active' => true, 'sort_order' => 6],
            ['religion' => 'Hindu', 'community_name' => 'Konkani', 'sub_communities' => json_encode(['Shetty', 'Prabhu', 'Konkani']), 'is_active' => true, 'sort_order' => 7],
            ['religion' => 'Hindu', 'community_name' => 'Devadiga', 'sub_communities' => json_encode(['Devadiga']), 'is_active' => true, 'sort_order' => 8],
            ['religion' => 'Hindu', 'community_name' => 'Kulala', 'sub_communities' => json_encode(['Kulal', 'Kulala']), 'is_active' => true, 'sort_order' => 9],
            ['religion' => 'Hindu', 'community_name' => 'Vishwakarma', 'sub_communities' => json_encode(['Achari', 'Vishwakarma']), 'is_active' => true, 'sort_order' => 10],
            ['religion' => 'Hindu', 'community_name' => 'Naik / Nayak', 'sub_communities' => json_encode(['Naik', 'Nayak']), 'is_active' => true, 'sort_order' => 11],
            ['religion' => 'Hindu', 'community_name' => 'Bhandari', 'sub_communities' => json_encode(['Bhandari']), 'is_active' => true, 'sort_order' => 12],
            ['religion' => 'Hindu', 'community_name' => 'Kota Brahmin', 'sub_communities' => json_encode(['Kota Brahmin']), 'is_active' => true, 'sort_order' => 13],
            ['religion' => 'Hindu', 'community_name' => 'Sthanika Brahmin', 'sub_communities' => json_encode(['Sthanika Brahmin']), 'is_active' => true, 'sort_order' => 14],
            ['religion' => 'Hindu', 'community_name' => 'Padmashali', 'sub_communities' => json_encode(['Padmashali']), 'is_active' => true, 'sort_order' => 15],
            ['religion' => 'Hindu', 'community_name' => 'Ganiga', 'sub_communities' => json_encode(['Ganiga']), 'is_active' => true, 'sort_order' => 16],
            ['religion' => 'Hindu', 'community_name' => 'Gudigar', 'sub_communities' => json_encode(['Gudigar']), 'is_active' => true, 'sort_order' => 17],
            ['religion' => 'Hindu', 'community_name' => 'Salian', 'sub_communities' => json_encode(['Salian']), 'is_active' => true, 'sort_order' => 18],
            ['religion' => 'Hindu', 'community_name' => 'Marathi', 'sub_communities' => json_encode(['Marathi']), 'is_active' => true, 'sort_order' => 19],
            ['religion' => 'Hindu', 'community_name' => 'Tulu Gowda', 'sub_communities' => json_encode(['Tulu Gowda']), 'is_active' => true, 'sort_order' => 20],
            ['religion' => 'Hindu', 'community_name' => 'Arer', 'sub_communities' => json_encode(['Arer']), 'is_active' => true, 'sort_order' => 21],

            // Christian - 5 communities
            ['religion' => 'Christian', 'community_name' => 'Roman Catholic', 'sub_communities' => json_encode(['Mangalorean Catholic', 'Konkani Catholic']), 'is_active' => true, 'sort_order' => 1],
            ['religion' => 'Christian', 'community_name' => 'Protestant', 'sub_communities' => json_encode(['CSI', 'Basel Mission', 'Methodist']), 'is_active' => true, 'sort_order' => 2],
            ['religion' => 'Christian', 'community_name' => 'Pentecostal', 'sub_communities' => json_encode(['Pentecostal']), 'is_active' => true, 'sort_order' => 3],
            ['religion' => 'Christian', 'community_name' => 'Syrian Christian', 'sub_communities' => json_encode(['Syrian Christian']), 'is_active' => true, 'sort_order' => 4],
            ['religion' => 'Christian', 'community_name' => 'Born Again', 'sub_communities' => json_encode(['Born Again']), 'is_active' => true, 'sort_order' => 5],

            // Muslim - 5 communities
            ['religion' => 'Muslim', 'community_name' => 'Beary', 'sub_communities' => json_encode(['Byari', 'Beary']), 'is_active' => true, 'sort_order' => 1],
            ['religion' => 'Muslim', 'community_name' => 'Nawayath', 'sub_communities' => json_encode(['Nawayath']), 'is_active' => true, 'sort_order' => 2],
            ['religion' => 'Muslim', 'community_name' => 'Mappila', 'sub_communities' => json_encode(['Mappila']), 'is_active' => true, 'sort_order' => 3],
            ['religion' => 'Muslim', 'community_name' => 'Sunni', 'sub_communities' => json_encode(['Sunni']), 'is_active' => true, 'sort_order' => 4],
            ['religion' => 'Muslim', 'community_name' => 'Shia', 'sub_communities' => json_encode(['Shia']), 'is_active' => true, 'sort_order' => 5],

            // Jain - 3 communities
            ['religion' => 'Jain', 'community_name' => 'Digambar Jain', 'sub_communities' => json_encode(['Digambar']), 'is_active' => true, 'sort_order' => 1],
            ['religion' => 'Jain', 'community_name' => 'Shvetambar Jain', 'sub_communities' => json_encode(['Shvetambar']), 'is_active' => true, 'sort_order' => 2],
            ['religion' => 'Jain', 'community_name' => 'Bunt Jain', 'sub_communities' => json_encode(['Bunt Jain']), 'is_active' => true, 'sort_order' => 3],
        ];

        foreach ($communities as $community) {
            Community::create($community);
        }
    }
}
