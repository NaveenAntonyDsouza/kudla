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
            ['religion' => 'Hindu', 'community_name' => 'Bunts', 'sub_communities' => (['Bunt', 'Jain Bunt', 'Shetty', 'Nadoja']), 'is_active' => true, 'sort_order' => 1],
            ['religion' => 'Hindu', 'community_name' => 'Billava', 'sub_communities' => (['Poojary', 'Billava']), 'is_active' => true, 'sort_order' => 2],
            ['religion' => 'Hindu', 'community_name' => 'Mogaveera', 'sub_communities' => (['Kharvi', 'Mogaveera']), 'is_active' => true, 'sort_order' => 3],
            ['religion' => 'Hindu', 'community_name' => 'GSB - Goud Saraswat Brahmin', 'sub_communities' => (['Shenoy', 'Pai', 'Kamath', 'Nayak', 'Baliga']), 'is_active' => true, 'sort_order' => 4],
            ['religion' => 'Hindu', 'community_name' => 'Havyaka Brahmin', 'sub_communities' => (['Havyaka']), 'is_active' => true, 'sort_order' => 5],
            ['religion' => 'Hindu', 'community_name' => 'Shivalli Brahmin', 'sub_communities' => (['Madhva Brahmin', 'Shivalli']), 'is_active' => true, 'sort_order' => 6],
            ['religion' => 'Hindu', 'community_name' => 'Konkani', 'sub_communities' => (['Shetty', 'Prabhu', 'Konkani']), 'is_active' => true, 'sort_order' => 7],
            ['religion' => 'Hindu', 'community_name' => 'Devadiga', 'sub_communities' => (['Devadiga']), 'is_active' => true, 'sort_order' => 8],
            ['religion' => 'Hindu', 'community_name' => 'Kulala', 'sub_communities' => (['Kulal', 'Kulala']), 'is_active' => true, 'sort_order' => 9],
            ['religion' => 'Hindu', 'community_name' => 'Vishwakarma', 'sub_communities' => (['Achari', 'Vishwakarma']), 'is_active' => true, 'sort_order' => 10],
            ['religion' => 'Hindu', 'community_name' => 'Naik / Nayak', 'sub_communities' => (['Naik', 'Nayak']), 'is_active' => true, 'sort_order' => 11],
            ['religion' => 'Hindu', 'community_name' => 'Bhandari', 'sub_communities' => (['Bhandari']), 'is_active' => true, 'sort_order' => 12],
            ['religion' => 'Hindu', 'community_name' => 'Kota Brahmin', 'sub_communities' => (['Kota Brahmin']), 'is_active' => true, 'sort_order' => 13],
            ['religion' => 'Hindu', 'community_name' => 'Sthanika Brahmin', 'sub_communities' => (['Sthanika Brahmin']), 'is_active' => true, 'sort_order' => 14],
            ['religion' => 'Hindu', 'community_name' => 'Padmashali', 'sub_communities' => (['Padmashali']), 'is_active' => true, 'sort_order' => 15],
            ['religion' => 'Hindu', 'community_name' => 'Ganiga', 'sub_communities' => (['Ganiga']), 'is_active' => true, 'sort_order' => 16],
            ['religion' => 'Hindu', 'community_name' => 'Gudigar', 'sub_communities' => (['Gudigar']), 'is_active' => true, 'sort_order' => 17],
            ['religion' => 'Hindu', 'community_name' => 'Salian', 'sub_communities' => (['Salian']), 'is_active' => true, 'sort_order' => 18],
            ['religion' => 'Hindu', 'community_name' => 'Marathi', 'sub_communities' => (['Marathi']), 'is_active' => true, 'sort_order' => 19],
            ['religion' => 'Hindu', 'community_name' => 'Tulu Gowda', 'sub_communities' => (['Tulu Gowda']), 'is_active' => true, 'sort_order' => 20],
            ['religion' => 'Hindu', 'community_name' => 'Arer', 'sub_communities' => (['Arer']), 'is_active' => true, 'sort_order' => 21],

            // Christian - 5 communities
            ['religion' => 'Christian', 'community_name' => 'Roman Catholic', 'sub_communities' => (['Mangalorean Catholic', 'Konkani Catholic']), 'is_active' => true, 'sort_order' => 1],
            ['religion' => 'Christian', 'community_name' => 'Protestant', 'sub_communities' => (['CSI', 'Basel Mission', 'Methodist']), 'is_active' => true, 'sort_order' => 2],
            ['religion' => 'Christian', 'community_name' => 'Pentecostal', 'sub_communities' => (['Pentecostal']), 'is_active' => true, 'sort_order' => 3],
            ['religion' => 'Christian', 'community_name' => 'Syrian Christian', 'sub_communities' => (['Syrian Christian']), 'is_active' => true, 'sort_order' => 4],
            ['religion' => 'Christian', 'community_name' => 'Born Again', 'sub_communities' => (['Born Again']), 'is_active' => true, 'sort_order' => 5],

            // Muslim - 5 communities
            ['religion' => 'Muslim', 'community_name' => 'Beary', 'sub_communities' => (['Byari', 'Beary']), 'is_active' => true, 'sort_order' => 1],
            ['religion' => 'Muslim', 'community_name' => 'Nawayath', 'sub_communities' => (['Nawayath']), 'is_active' => true, 'sort_order' => 2],
            ['religion' => 'Muslim', 'community_name' => 'Mappila', 'sub_communities' => (['Mappila']), 'is_active' => true, 'sort_order' => 3],
            ['religion' => 'Muslim', 'community_name' => 'Sunni', 'sub_communities' => (['Sunni']), 'is_active' => true, 'sort_order' => 4],
            ['religion' => 'Muslim', 'community_name' => 'Shia', 'sub_communities' => (['Shia']), 'is_active' => true, 'sort_order' => 5],

            // Jain - 3 communities
            ['religion' => 'Jain', 'community_name' => 'Digambar Jain', 'sub_communities' => (['Digambar']), 'is_active' => true, 'sort_order' => 1],
            ['religion' => 'Jain', 'community_name' => 'Shvetambar Jain', 'sub_communities' => (['Shvetambar']), 'is_active' => true, 'sort_order' => 2],
            ['religion' => 'Jain', 'community_name' => 'Bunt Jain', 'sub_communities' => (['Bunt Jain']), 'is_active' => true, 'sort_order' => 3],
        ];

        foreach ($communities as $community) {
            Community::create($community);
        }
    }
}
