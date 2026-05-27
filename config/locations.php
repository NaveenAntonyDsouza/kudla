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

    // First-level subdivisions (states / provinces / emirates / governorates /
    // regions) for the foreign countries our diaspora members come from most.
    // Drives the "Working State" / "Native State" dropdown on the registration
    // forms for non-India countries (India uses 'indian_states' above).
    //
    // IMPORTANT: the keys MUST match the country VALUE strings in
    // config('reference_data.country_list') exactly — e.g. 'United Kingdom'
    // (not 'UK'), 'USA', 'UAE'. A mismatch silently returns no states.
    //
    // Any country NOT listed here (incl. the city/micro-states Singapore &
    // Malta, and the long tail of "Other Countries") falls back to a free-text
    // state input — so a member can always enter their state. Member profile
    // edit uses free text throughout, so this only shapes the registration flow.
    'country_state_map' => [
        // USA — 50 states + DC.
        'USA' => [
            'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado',
            'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho',
            'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana',
            'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota',
            'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada',
            'New Hampshire', 'New Jersey', 'New Mexico', 'New York',
            'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon',
            'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota',
            'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington',
            'Washington D.C.', 'West Virginia', 'Wisconsin', 'Wyoming',
        ],
        // UAE — 7 emirates.
        'UAE' => [
            'Abu Dhabi', 'Ajman', 'Dubai', 'Fujairah', 'Ras Al Khaimah',
            'Sharjah', 'Umm Al Quwain',
        ],
        // United Kingdom — 4 constituent nations.
        'United Kingdom' => [
            'England', 'Northern Ireland', 'Scotland', 'Wales',
        ],
        // Canada — 10 provinces + 3 territories.
        'Canada' => [
            'Alberta', 'British Columbia', 'Manitoba', 'New Brunswick',
            'Newfoundland and Labrador', 'Northwest Territories', 'Nova Scotia',
            'Nunavut', 'Ontario', 'Prince Edward Island', 'Quebec',
            'Saskatchewan', 'Yukon',
        ],
        // Australia — 6 states + 2 territories.
        'Australia' => [
            'Australian Capital Territory', 'New South Wales', 'Northern Territory',
            'Queensland', 'South Australia', 'Tasmania', 'Victoria',
            'Western Australia',
        ],
        // Saudi Arabia — 13 regions.
        'Saudi Arabia' => [
            'Al Bahah', 'Al Jawf', 'Al Madinah', 'Al Qassim', 'Asir',
            'Eastern Province', 'Hail', 'Jazan', 'Makkah', 'Najran',
            'Northern Borders', 'Riyadh', 'Tabuk',
        ],
        // Qatar — 8 municipalities.
        'Qatar' => [
            'Al Daayen', 'Al Khor', 'Al Rayyan', 'Al Shahaniya', 'Al Shamal',
            'Al Wakrah', 'Doha', 'Umm Salal',
        ],
        // Oman — 11 governorates.
        'Oman' => [
            'Ad Dakhiliyah', 'Adh Dhahirah', 'Al Batinah North', 'Al Batinah South',
            'Al Buraimi', 'Al Wusta', 'Ash Sharqiyah North', 'Ash Sharqiyah South',
            'Dhofar', 'Muscat', 'Musandam',
        ],
        // Kuwait — 6 governorates.
        'Kuwait' => [
            'Al Ahmadi', 'Al Asimah', 'Al Farwaniyah', 'Al Jahra', 'Hawalli',
            'Mubarak Al-Kabeer',
        ],
        // Bahrain — 4 governorates (Central was dissolved in 2014).
        'Bahrain' => [
            'Capital', 'Muharraq', 'Northern', 'Southern',
        ],
        // New Zealand — 16 regions.
        'New Zealand' => [
            'Auckland', 'Bay of Plenty', 'Canterbury', 'Gisborne', 'Hawke\'s Bay',
            'Manawatu-Whanganui', 'Marlborough', 'Nelson', 'Northland', 'Otago',
            'Southland', 'Taranaki', 'Tasman', 'Waikato', 'Wellington',
            'West Coast',
        ],
        // Switzerland — 26 cantons.
        'Switzerland' => [
            'Aargau', 'Appenzell Ausserrhoden', 'Appenzell Innerrhoden',
            'Basel-Landschaft', 'Basel-Stadt', 'Bern', 'Fribourg', 'Geneva',
            'Glarus', 'Graubunden', 'Jura', 'Lucerne', 'Neuchatel', 'Nidwalden',
            'Obwalden', 'Schaffhausen', 'Schwyz', 'Solothurn', 'St. Gallen',
            'Thurgau', 'Ticino', 'Uri', 'Valais', 'Vaud', 'Zug', 'Zurich',
        ],
        // Germany — 16 states (Bundesländer).
        'Germany' => [
            'Baden-Wurttemberg', 'Bavaria', 'Berlin', 'Brandenburg', 'Bremen',
            'Hamburg', 'Hesse', 'Lower Saxony', 'Mecklenburg-Vorpommern',
            'North Rhine-Westphalia', 'Rhineland-Palatinate', 'Saarland',
            'Saxony', 'Saxony-Anhalt', 'Schleswig-Holstein', 'Thuringia',
        ],
        // France — 13 metropolitan regions.
        'France' => [
            'Auvergne-Rhone-Alpes', 'Bourgogne-Franche-Comte', 'Brittany',
            'Centre-Val de Loire', 'Corsica', 'Grand Est', 'Hauts-de-France',
            'Ile-de-France', 'Normandy', 'Nouvelle-Aquitaine', 'Occitanie',
            'Pays de la Loire', 'Provence-Alpes-Cote d\'Azur',
        ],
        // Ireland — 26 counties (Republic of Ireland).
        'Ireland' => [
            'Carlow', 'Cavan', 'Clare', 'Cork', 'Donegal', 'Dublin', 'Galway',
            'Kerry', 'Kildare', 'Kilkenny', 'Laois', 'Leitrim', 'Limerick',
            'Longford', 'Louth', 'Mayo', 'Meath', 'Monaghan', 'Offaly',
            'Roscommon', 'Sligo', 'Tipperary', 'Waterford', 'Westmeath',
            'Wexford', 'Wicklow',
        ],
        // Italy — 20 regions.
        'Italy' => [
            'Abruzzo', 'Aosta Valley', 'Apulia', 'Basilicata', 'Calabria',
            'Campania', 'Emilia-Romagna', 'Friuli-Venezia Giulia', 'Lazio',
            'Liguria', 'Lombardy', 'Marche', 'Molise', 'Piedmont', 'Sardinia',
            'Sicily', 'Trentino-Alto Adige', 'Tuscany', 'Umbria', 'Veneto',
        ],
        // Malaysia — 13 states + 3 federal territories.
        'Malaysia' => [
            'Johor', 'Kedah', 'Kelantan', 'Kuala Lumpur', 'Labuan', 'Malacca',
            'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Putrajaya',
            'Sabah', 'Sarawak', 'Selangor', 'Terengganu',
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
