<?php

/*
|--------------------------------------------------------------------------
| Profession groups (Discover → Profession Matrimony, homepage "Profession")
|--------------------------------------------------------------------------
| Oct 2026: the homepage listed 108 job titles led by vague ones ("Manager",
| "Supervisors"), and the occupation list's own categories can't be shown to
| families ("Below Graduate"). Families search the way they talk: "doctor
| matrimony", "engineer matrimony", "government employee matrimony", "teacher
| matrimony". So each group has a plain, searchable name and holds job titles
| from reference_data.occupation_category_list (exact spelling). A title in no
| group (Student, Not Working, ...) is listed under 'unassigned' and still has
| its own page under Occupation Matrimony. Every title is in exactly one place
| (tests/Feature/ProfessionGroupsTest.php checks this).
|
| 'about' finishes the sentence "brides and grooms working in ...".
*/

return [
    'groups' => [
        [
            'label' => 'Engineers & IT', 'slug' => 'engineers-it', 'about' => 'engineering, software and IT',
            'occupations' => ['Aeronautical Engineer', 'Biomedical Engineer', 'Chemical Engineer', 'Civil Engineer', 'Cyber/Network Security',
                'Data Analyst', 'Data Scientist', 'Electronics/Telecom Engineer', 'Engineer', 'Engineer Non IT', 'Hardware Professional',
                'Mechanical/Production Engineer', 'Network Engineer', 'Product Manager - IT', 'Project Manager - IT', 'Software Consultant',
                'Software Professional', 'UI/UX Designer', 'UX Writer', 'Web Designer', 'Surveyor'],
        ],
        [
            'label' => 'Doctors & Nurses', 'slug' => 'doctors-nurses', 'about' => 'medicine, nursing and healthcare',
            'occupations' => ['Allopathic Doctor', 'Audiologist', 'Ayurvedic Doctor', 'CT Technician', 'Dentist', 'Dietitian', 'Health Care Assistant',
                'Homeo Doctor', 'Laboratory Technician', 'Medical Coder', 'Medical Transcription', 'Microbiologist', 'MRI Technician',
                'Naturopathic Doctor', 'Nutritionist', 'Occupational Therapist', 'Optometrist', 'Paramedical Professional', 'Perfusionists',
                'PG Doctors / Surgeons', 'Pharmacist', 'Physician Assistant', 'Physiotherapist', 'Psychologist', 'Radiologist', 'Therapist',
                'Veterinary Doctor', 'Xray Technician', 'B.Sc Nurse', 'General Nurse', 'M.Sc Nurse', 'Nursing Assistance', 'Nursing Tutor'],
        ],
        [
            'label' => 'Teachers & Lecturers', 'slug' => 'teachers-lecturers', 'about' => 'teaching and education',
            'occupations' => ['Academic Counsellor', 'Education Professional', 'Professor/Lecturer', 'Student Counsellor', 'Teaching/Academician',
                'Tutor', 'Non Teaching', 'Librarian'],
        ],
        [
            'label' => 'Government & Defence', 'slug' => 'government-defence', 'about' => 'government service, defence and the police',
            'occupations' => ['Civil Service', 'Defence', 'Govt.Service', 'Paramilitary', 'Police', 'Captain Central Gov.', 'Loco Pilot'],
        ],
        [
            'label' => 'Banking & Finance', 'slug' => 'banking-finance', 'about' => 'banking, accounts and finance',
            'occupations' => ['Accounts/Finance Professional', 'Auditor', 'Bank Manager/Asst. Manager', 'Banking Service Professional', 'Cashier',
                'Chartered Accountant', 'Chartered Financial Analyst', 'Company Secretary', 'Cost Accountant', 'Investment Professional',
                'Insurance agent'],
        ],
        [
            'label' => 'Business & Self-employed', 'slug' => 'business-self-employed', 'about' => 'their own business',
            'occupations' => ['Business', 'Self Employed', 'Entrepreneur', 'Contractor', 'Trader', 'Freelancer'],
        ],
        [
            'label' => 'Corporate & Office', 'slug' => 'corporate-office', 'about' => 'company and office jobs',
            'occupations' => ['Administrative Professional', 'Clerk', 'Corporate communication', 'Corporate Planning', 'Executive', 'HR Executive',
                'HR Manager', 'Manager', 'Officer', 'Operations management', 'Supervisors', 'Team Lead', 'CXO/President/Director/Chairman',
                'VP/AVP/DGM/GM/AGM', 'Analyst', 'Associate', 'BPO/KPO/ITES Professional', 'Business Analyst', 'Business Development Professional',
                'Consultant', 'Coordinator', 'Customer Care Professional', 'Logistics Operations', 'Merchandiser', 'Project Manager - Non IT',
                'Quality Controller', 'Receptionist', 'Office staff', 'Tele Caller', 'Computer Operator', 'Store Manager', 'Safety Officer',
                'Product manager - Non IT'],
        ],
        [
            'label' => 'Sales & Marketing', 'slug' => 'sales-marketing', 'about' => 'sales and marketing',
            'occupations' => ['Digital Marketer', 'Marketing Professional', 'Sales Professional', 'Medical Representative'],
        ],
        [
            'label' => 'Skilled Trades & Technicians', 'slug' => 'skilled-trades-technicians', 'about' => 'skilled trades and technical work',
            'occupations' => ['Bus Conductor', 'Carpenter', 'Driver', 'Electrician', 'Mason', 'Mechanic', 'Oil & Gas Professional', 'Operator',
                'Plumber', 'Security Guard', 'Skilled Worker', 'Tailor', 'Welder', 'Technician', 'Construction Work', 'Peon', 'Sweeper'],
        ],
        [
            'label' => 'Agriculture & Fishing', 'slug' => 'agriculture-fishing', 'about' => 'farming and fishing',
            'occupations' => ['Agriculture & Farming Professional', 'Farm Manager', 'Agriculture (Occupation)', 'Fishermen', 'Srank'],
        ],
        [
            'label' => 'Hospitality & Aviation', 'slug' => 'hospitality-aviation', 'about' => 'hotels, airlines and shipping',
            'occupations' => ['Chef', 'Event planner', 'Hotel/ Hospitality Professional', 'Restaurant/Catering Professional', 'Air Hostess',
                'Air Traffic Controller', 'Aircraft mechanic', 'Airline Professional', 'Airport executive', 'Cabin Crew', 'Pilot',
                'Mariner/Merchant Navy', 'Sailor'],
        ],
        [
            'label' => 'Design, Media & Fashion', 'slug' => 'design-media-fashion', 'about' => 'design, media and fashion',
            'occupations' => ['Architect', 'Draftman', 'Interior Designer', 'Sculpturist', 'Beautician', 'Fashion Designer', 'Make up Artist',
                'Modeling', '3D/VFX Animator', 'Actor/Actress', 'Advertising/PR Professional', 'Anchor', 'Entertainment Professional',
                'Event Management Professional', 'Graphic Designer', 'Industrial Designer', 'Journalist', 'Media Professional',
                'Videographer/Photographer', 'Singer', 'Visual Communication Designer', 'Arts & Craftsman', 'Content writer', 'Writer'],
        ],
        [
            'label' => 'Lawyers & Legal', 'slug' => 'lawyers-legal', 'about' => 'law',
            'occupations' => ['Corporate Lawyer', 'Judicial Services', 'Lawyer & Legal Professional', 'Legal Company Secretary', 'Legal Officer'],
        ],
        [
            'label' => 'Scientists & Researchers', 'slug' => 'scientists-researchers', 'about' => 'science and research',
            'occupations' => ['Scientist/Researcher', 'Chemist'],
        ],
        [
            'label' => 'Sports & Fitness', 'slug' => 'sports-fitness', 'about' => 'sports and fitness',
            'occupations' => ['Fitness Professional', 'Sports Trainer', 'Sportsman', 'Physical Trainer'],
        ],
    ],

    // Job titles in no group: not a profession group families would browse.
    'unassigned' => ['Student', 'Not Working', 'Internship', 'Part Time Job', 'Trainee', 'Priest', 'Deacon', 'Politician',
        'Social Worker', 'Counselor'],
];
