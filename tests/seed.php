<?php
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/../backend/models/User.php';
require_once __DIR__ . '/../backend/models/Location.php';

$db = Database::getInstance()->getConnection();
$userModel = new User();
$locationModel = new Location();

echo "Seeding database...\n";

// 1. Create a Vendor (in vendors table)
$vendorEmail = 'vendor@city.com';
if(!$userModel->isEmailTaken($vendorEmail)) {
    $vendorId = $userModel->create([
        'full_name' => 'City Infrastructure Ltd',
        'email' => $vendorEmail,
        'password' => 'vendor123',
        'phone' => '9800000000',
        'role' => 'vendor',
        'business_name' => 'City Infrastructure Ltd',
        'pan_number' => '123456789',
        'address' => 'Main Road, Hetauda'
    ]);
    echo "Created Vendor ID: $vendorId\n";
} else {
    $vendor = $userModel->validateCredentials($vendorEmail, 'vendor123');
    $vendorId = $vendor['id'];
    echo "Using Vendor ID: $vendorId\n";
}

// 2. Seed Locations
$locations = [
    [
        'name' => 'Hetauda City Center Hub',
        'address' => 'Main Road, Hetauda',
        'description' => 'Central parking near the city center with 24/7 security.',
        'price_per_hour' => 50.00,
        'total_slots' => 100,
        'latitude' => 27.4293,
        'longitude' => 85.0305
    ],
    [
        'name' => 'Bus Park Smart Zone',
        'address' => 'Bus Park, Hetauda',
        'description' => 'Convenient parking near the bus terminal.',
        'price_per_hour' => 30.00,
        'total_slots' => 45,
        'latitude' => 27.4200,
        'longitude' => 85.0200
    ],
    [
        'name' => 'Huprachaur Recreational',
        'address' => 'Huprachaur, Hetauda',
        'description' => 'Secure night parking available near recreational area.',
        'price_per_hour' => 25.00,
        'total_slots' => 60,
        'latitude' => 27.4350,
        'longitude' => 85.0400
    ]
];

foreach ($locations as $data) {
    $stmt = $db->prepare("SELECT id FROM locations WHERE name = ?");
    $stmt->execute([$data['name']]);
    if($stmt->rowCount() == 0) {
        $id = $locationModel->create($vendorId, $data);
        $locationModel->approveLocation($id);
        echo "Created & Approved Location: {$data['name']}\n";
    } else {
        echo "Skipped existing: {$data['name']}\n";
    }
}

echo "Database seeded successfully!\n";
?>
