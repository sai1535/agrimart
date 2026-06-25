<?php
include 'includes/db.php';

try {
    echo "Starting database migration...\n";

    // Disable foreign key checks to drop tables cleanly
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Drop existing tables if they exist
    $tables = ['order_items', 'orders', 'cart', 'products', 'users'];
    foreach ($tables as $table) {
        $conn->exec("DROP TABLE IF EXISTS `$table`;");
        echo "Dropped table $table (if it existed).\n";
    }

    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 1. Create Users Table
    $conn->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100) DEFAULT NULL,
        phone VARCHAR(15) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        role ENUM('farmer', 'admin') DEFAULT 'farmer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Created table 'users'.\n";

    // 2. Create Products Table
    $conn->exec("CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        description TEXT DEFAULT NULL,
        image VARCHAR(255) DEFAULT NULL,
        category VARCHAR(50) NOT NULL,
        crop_type VARCHAR(50) NOT NULL,
        stock INT DEFAULT 100,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Created table 'products'.\n";

    // 3. Create Cart Table
    $conn->exec("CREATE TABLE cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Created table 'cart'.\n";

    // 4. Create Orders Table
    $conn->exec("CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        payment_status VARCHAR(20) DEFAULT 'Pending',
        tracking_status VARCHAR(50) DEFAULT 'Order Placed',
        shipping_address TEXT NOT NULL,
        phone VARCHAR(15) NOT NULL,
        payment_id VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Created table 'orders'.\n";

    // 5. Create Order Items Table
    $conn->exec("CREATE TABLE order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Created table 'order_items'.\n";

    // Seeding sample product data
    echo "Seeding products table...\n";
    $products = [
        // Paddy Products
        [
            'name' => 'IR-64 Paddy Seeds (High Yield)',
            'price' => 25.00,
            'description' => 'Premium grade IR-64 paddy crop seeds. Excellent disease resistance and high milling recovery rate, suitable for irrigated areas.',
            'image' => 'paddy_seeds.jpg',
            'category' => 'Seeds',
            'crop_type' => 'Paddy',
            'stock' => 150
        ],
        [
            'name' => 'Urea Nitrogen Fertilizer (46% N)',
            'price' => 12.50,
            'description' => 'High-quality agricultural Urea. Vital fertilizer for vegetative growth, leaf color, and high yield in paddy crops.',
            'image' => 'urea.jpg',
            'category' => 'Fertilizers',
            'crop_type' => 'Paddy',
            'stock' => 500
        ],
        [
            'name' => 'Gromor 14-35-14 Complex Fertilizer',
            'price' => 18.99,
            'description' => 'Perfect blend of NPK (14:35:14) for root development, strong stalks, and heavy grain filling in paddy crops.',
            'image' => 'gromor.jpg',
            'category' => 'Fertilizers',
            'crop_type' => 'Paddy',
            'stock' => 350
        ],
        [
            'name' => 'Tricyclazole 75% WP Blast Pesticide',
            'price' => 15.50,
            'description' => 'Systemic fungicide for control of Leaf and Neck Blast in Paddy fields. Highly effective preventive action.',
            'image' => 'pesticide_blast.jpg',
            'category' => 'Pesticides',
            'crop_type' => 'Paddy',
            'stock' => 120
        ],

        // Wheat Products
        [
            'name' => 'HD-2967 Wheat Seeds',
            'price' => 28.00,
            'description' => 'Popular hybrid wheat seed variety. Resistance to yellow rust and high heat tolerance, giving excellent grain size.',
            'image' => 'wheat_seeds.jpg',
            'category' => 'Seeds',
            'crop_type' => 'Wheat',
            'stock' => 200
        ],
        [
            'name' => 'NPK 19-19-19 Water Soluble Fertilizer',
            'price' => 14.00,
            'description' => 'Balanced water-soluble NPK fertilizer. Enhances vegetative growth, flowering, and wheat seed yield.',
            'image' => 'npk_19.jpg',
            'category' => 'Fertilizers',
            'crop_type' => 'Wheat',
            'stock' => 250
        ],
        [
            'name' => 'Clodinafop-propargyl 15% WP Weedicide',
            'price' => 9.99,
            'description' => 'Selective herbicide for the control of Phalaris minor and wild oats in wheat crops.',
            'image' => 'weedicide_wheat.jpg',
            'category' => 'Pesticides',
            'crop_type' => 'Wheat',
            'stock' => 180
        ],

        // Cotton Products
        [
            'name' => 'Bt Cotton Hybrid Seeds',
            'price' => 35.00,
            'description' => 'Genetically modified bollworm-resistant Bt cotton hybrid seeds. Exceptional fiber quality and staple length.',
            'image' => 'cotton_seeds.jpg',
            'category' => 'Seeds',
            'crop_type' => 'Cotton',
            'stock' => 80
        ],
        [
            'name' => 'Monocrotophos 36% SL Bollworm Pesticide',
            'price' => 22.00,
            'description' => 'Systemic insecticide for effective control of sucking pests and bollworms in cotton crops.',
            'image' => 'pesticide_cotton.jpg',
            'category' => 'Pesticides',
            'crop_type' => 'Cotton',
            'stock' => 90
        ],
        [
            'name' => 'Magnesium Sulfate Fertilizer (Epsom Salt)',
            'price' => 8.50,
            'description' => 'Corrects magnesium deficiency in soil. Essential for preventing leaf reddening and improving cotton yields.',
            'image' => 'magnesium_sulfate.jpg',
            'category' => 'Fertilizers',
            'crop_type' => 'Cotton',
            'stock' => 300
        ],

        // Vegetables & General
        [
            'name' => 'Hybrid Tomato Seeds (F1)',
            'price' => 5.00,
            'description' => 'High germination hybrid tomato seeds. Produces glossy, firm, and uniform red tomatoes.',
            'image' => 'tomato_seeds.jpg',
            'category' => 'Seeds',
            'crop_type' => 'Vegetables',
            'stock' => 150
        ],
        [
            'name' => 'Pure Organic Vermicompost Manure',
            'price' => 10.00,
            'description' => '100% organic earthworm compost. Enriches soil structure, aeration, and water retention capacity.',
            'image' => 'vermicompost.jpg',
            'category' => 'Fertilizers',
            'crop_type' => 'Vegetables',
            'stock' => 400
        ],
        [
            'name' => 'Neem Oil Bio-Pesticide (10000 PPM)',
            'price' => 11.20,
            'description' => 'Natural organic insecticide and pest repellent. Eco-friendly solution for leaf miners, aphids, and mites.',
            'image' => 'neem_oil.jpg',
            'category' => 'Pesticides',
            'crop_type' => 'Vegetables',
            'stock' => 140
        ],
        [
            'name' => 'Knapsack Manual Crop Sprayer (16L)',
            'price' => 45.00,
            'description' => 'Heavy-duty 16-liter manual backpack sprayer. Equipped with continuous pressure nozzle for fertilizers and pesticides.',
            'image' => 'knapsack_sprayer.jpg',
            'category' => 'Tools',
            'crop_type' => 'General',
            'stock' => 50
        ],
        [
            'name' => 'Farming Hand Hoe & Digging Tool',
            'price' => 19.99,
            'description' => 'Hardened carbon steel blade with ergonomic handle. Ideal tool for weeding, digging, and soil turning.',
            'image' => 'hoe.jpg',
            'category' => 'Tools',
            'crop_type' => 'General',
            'stock' => 75
        ]
    ];

    $stmt = $conn->prepare("INSERT INTO products (name, price, description, image, category, crop_type, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($products as $p) {
        $stmt->execute([
            $p['name'],
            $p['price'],
            $p['description'],
            $p['image'],
            $p['category'],
            $p['crop_type'],
            $p['stock']
        ]);
        echo "Inserted product: {$p['name']}\n";
    }

    echo "Database setup completed successfully!\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
