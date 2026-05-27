<?php

return [
    // Complete current district lists for the states our members come from most
    // (South India + Maharashtra). States not listed here, and any state whose
    // list is ever out of date, fall back to a free-text district input on the
    // registration forms (datalist autocomplete), so a member can always enter
    // their district — these lists drive suggestions, they are not a hard gate.
    'state_district_map' => [
        // Karnataka — 31 districts (file uses the popular/older spellings members
        // recognise, e.g. Bangalore/Mysore/Shimoga, not the gazette renames).
        'Karnataka' => [
            'Bagalkot', 'Bangalore Rural', 'Bangalore Urban', 'Belgaum', 'Bellary',
            'Bidar', 'Chamarajanagar', 'Chikkaballapur', 'Chikkamagaluru', 'Chitradurga',
            'Dakshina Kannada', 'Davanagere', 'Dharwad', 'Gadag', 'Gulbarga',
            'Hassan', 'Haveri', 'Kodagu', 'Kolar', 'Koppal',
            'Mandya', 'Mysore', 'Raichur', 'Ramanagara', 'Shimoga',
            'Tumkur', 'Udupi', 'Uttara Kannada', 'Vijayanagara', 'Vijayapura',
            'Yadgir',
        ],
        // Kerala — all 14 districts.
        'Kerala' => [
            'Alappuzha', 'Ernakulam', 'Idukki', 'Kannur', 'Kasaragod',
            'Kollam', 'Kottayam', 'Kozhikode', 'Malappuram', 'Palakkad',
            'Pathanamthitta', 'Thiruvananthapuram', 'Thrissur', 'Wayanad',
        ],
        // Tamil Nadu — all 38 districts (post-2020 reorganisation).
        'Tamil Nadu' => [
            'Ariyalur', 'Chengalpattu', 'Chennai', 'Coimbatore', 'Cuddalore',
            'Dharmapuri', 'Dindigul', 'Erode', 'Kallakurichi', 'Kanchipuram',
            'Kanyakumari', 'Karur', 'Krishnagiri', 'Madurai', 'Mayiladuthurai',
            'Nagapattinam', 'Namakkal', 'Nilgiris', 'Perambalur', 'Pudukkottai',
            'Ramanathapuram', 'Ranipet', 'Salem', 'Sivaganga', 'Tenkasi',
            'Thanjavur', 'Theni', 'Thoothukudi', 'Tiruchirappalli', 'Tirunelveli',
            'Tirupathur', 'Tiruppur', 'Tiruvallur', 'Tiruvannamalai', 'Tiruvarur',
            'Vellore', 'Viluppuram', 'Virudhunagar',
        ],
        // Maharashtra — all 36 districts.
        'Maharashtra' => [
            'Ahmednagar', 'Akola', 'Amravati', 'Aurangabad', 'Beed',
            'Bhandara', 'Buldhana', 'Chandrapur', 'Dhule', 'Gadchiroli',
            'Gondia', 'Hingoli', 'Jalgaon', 'Jalna', 'Kolhapur',
            'Latur', 'Mumbai City', 'Mumbai Suburban', 'Nagpur', 'Nanded',
            'Nandurbar', 'Nashik', 'Osmanabad', 'Palghar', 'Parbhani',
            'Pune', 'Raigad', 'Ratnagiri', 'Sangli', 'Satara',
            'Sindhudurg', 'Solapur', 'Thane', 'Wardha', 'Washim',
            'Yavatmal',
        ],
        'Goa' => ['North Goa', 'South Goa'],
        // Andhra Pradesh — all 26 districts (post-2022 reorganisation).
        'Andhra Pradesh' => [
            'Alluri Sitharama Raju', 'Anakapalli', 'Anantapur', 'Annamayya', 'Bapatla',
            'Chittoor', 'Dr. B.R. Ambedkar Konaseema', 'East Godavari', 'Eluru', 'Guntur',
            'Kakinada', 'Krishna', 'Kurnool', 'Nandyal', 'NTR',
            'Palnadu', 'Parvathipuram Manyam', 'Prakasam', 'Sri Potti Sriramulu Nellore', 'Sri Sathya Sai',
            'Srikakulam', 'Tirupati', 'Visakhapatnam', 'Vizianagaram', 'West Godavari',
            'YSR Kadapa',
        ],
        // Telangana — all 33 districts.
        'Telangana' => [
            'Adilabad', 'Bhadradri Kothagudem', 'Hanumakonda', 'Hyderabad', 'Jagtial',
            'Jangaon', 'Jayashankar Bhupalpally', 'Jogulamba Gadwal', 'Kamareddy', 'Karimnagar',
            'Khammam', 'Kumuram Bheem Asifabad', 'Mahabubabad', 'Mahabubnagar', 'Mancherial',
            'Medak', 'Medchal-Malkajgiri', 'Mulugu', 'Nagarkurnool', 'Nalgonda',
            'Narayanpet', 'Nirmal', 'Nizamabad', 'Peddapalli', 'Rajanna Sircilla',
            'Rangareddy', 'Sangareddy', 'Siddipet', 'Suryapet', 'Vikarabad',
            'Wanaparthy', 'Warangal', 'Yadadri Bhuvanagiri',
        ],
    ],
    'countries' => [
        'India', 'UAE', 'Oman', 'Qatar', 'Kuwait', 'Saudi Arabia', 'Bahrain',
        'USA', 'UK', 'Canada', 'Australia', 'Singapore', 'Germany', 'New Zealand',
        'Ireland', 'Malaysia', 'Japan', 'South Korea', 'Italy', 'France', 'Other',
    ],
    'indian_states' => [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
        'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
        'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
        'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
        'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
    ],
];
