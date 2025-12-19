<?php
/*
Install Script for Rau Câu Shop
- Creates database + tables
- Inserts sample data
- Creates config.php
*/

// When user clicks Install
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $host = $_POST['db_host'] ?? '127.0.0.1';
    $user = $_POST['db_user'] ?? 'root';
    $pass = $_POST['db_pass'] ?? '';
    $name = $_POST['db_name'] ?? 'raucaushop';

    // Connect to MySQL (no DB yet)
    $mysqli = new mysqli($host, $user, $pass);
    if($mysqli->connect_errno){
        $err = "Connection failed: ".$mysqli->connect_error;
    } else {

        // CREATE DATABASE
        $createDbQuery = "CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        if(!$mysqli->query($createDbQuery)){
            $err = "Failed to create database: ".$mysqli->error;
            $mysqli->close();
        } else {
            // Select the database
            if(!$mysqli->select_db($name)){
                $err = "Failed to select database '$name': ".$mysqli->error;
                $mysqli->close();
            } else {
                // Set charset
                $mysqli->set_charset('utf8mb4');

                // CREATE TABLE: admin_users
                $createAdminTable = "
                CREATE TABLE IF NOT EXISTS admin_users(
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ";
                if(!$mysqli->query($createAdminTable)){
                    $err = "Failed to create admin_users table: ".$mysqli->error;
                    $mysqli->close();
                } else {
                    // CREATE TABLE: products
                    $createProductsTable = "
                    CREATE TABLE IF NOT EXISTS products(
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        description TEXT,
                        price INT NOT NULL,
                        image VARCHAR(255),
                        category VARCHAR(100),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    ";
                    if(!$mysqli->query($createProductsTable)){
                        $err = "Failed to create products table: ".$mysqli->error;
                        $mysqli->close();
                    } else {
                        // CREATE TABLE: customers
                        $createCustomersTable = "
                        CREATE TABLE IF NOT EXISTS customers(
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            full_name VARCHAR(255) NOT NULL,
                            email VARCHAR(255) NOT NULL UNIQUE,
                            password_hash VARCHAR(255) NOT NULL,
                            phone VARCHAR(50),
                            address TEXT,
                            is_locked TINYINT(1) NOT NULL DEFAULT 0,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                        ";
                        if(!$mysqli->query($createCustomersTable)){
                            $err = "Failed to create customers table: ".$mysqli->error;
                            $mysqli->close();
                        } else {
                            // CREATE TABLE: orders
                            $createOrdersTable = "
                            CREATE TABLE IF NOT EXISTS orders(
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                order_code VARCHAR(50),
                                customer_id INT,
                                customer_name VARCHAR(255),
                                customer_phone VARCHAR(50),
                                customer_address TEXT,
                                total_price INT DEFAULT 0,
                                shipped TINYINT(1) DEFAULT 0,
                                cancelled TINYINT(1) DEFAULT 0,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                            ";
                            if(!$mysqli->query($createOrdersTable)){
                                $err = "Failed to create orders table: ".$mysqli->error;
                                $mysqli->close();
                            } else {
                                // CREATE TABLE: order_items
                                $createOrderItemsTable = "
                                CREATE TABLE IF NOT EXISTS order_items(
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    order_id INT,
                                    product_id INT,
                                    quantity INT,
                                    price INT,
                                    FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
                                    FOREIGN KEY(product_id) REFERENCES products(id)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                                ";
                                if(!$mysqli->query($createOrderItemsTable)){
                                    $err = "Failed to create order_items table: ".$mysqli->error;
                                    $mysqli->close();
                                } else {
                                    // CREATE DEFAULT ADMIN USER
                                    $admin_user = "admin";
                                    $admin_pass = "admin123";
                                    $hash = password_hash($admin_pass, PASSWORD_DEFAULT);

                                    $stmt = $mysqli->prepare("INSERT IGNORE INTO admin_users(username,password_hash) VALUES(?,?)");
                                    if($stmt){
                                        $stmt->bind_param("ss", $admin_user, $hash);
                                        if(!$stmt->execute()){
                                            $err = "Failed to create admin user: ".$stmt->error;
                                        }
                                        $stmt->close();
                                    } else {
                                        $err = "Failed to prepare admin user statement: ".$mysqli->error;
                                    }

                                    // Ensure images folder exists
                                    $imagesDir = __DIR__.'/public/assets/images';
                                    if(!is_dir($imagesDir)){
                                        if(!mkdir($imagesDir, 0755, true)){
                                            $err = "Failed to create images directory";
                                        }
                                    }

                                    // INSERT SAMPLE PRODUCTS
                                    if(!isset($err)){
                                        $products = [
                                            ["Coconut Jelly", "Rich traditional coconut jelly, 200g", 30000, "assets/images/raucaudua.png", "Traditional"],
                                            ["Pandan Jelly", "Fragrant pandan jelly, 200g", 32000, "assets/images/ladua.png", "Traditional"],
                                            ["Milk & Caramel Flan Jelly", "Milk jelly combined with soft flan, 200g", 38000, "assets/images/flan.png", "Dessert"],
                                            ["Strawberry Jelly", "Lightly sweet strawberry jelly, 200g", 35000, "assets/images/dau.png", "Fruit"],
                                            ['Kiwi Jelly', 'Sweet and tangy kiwi jelly, 200g', 37000, 'assets/images/kiwi.png', 'Fruit'],
                                            ['Milk Tea Jelly', 'Delicate milk tea flavored jelly, 200g', 41000, 'assets/images/trasua.png', 'Coffee & Tea'],
                                            ['Chocolate Pudding Jelly', 'Jelly combined with smooth chocolate pudding, 200g', 39000, 'assets/images/puddingsocola.png', 'Dessert'],
                                            ["Coffee Jelly", "Rich coffee flavored jelly, 200g", 42000, "assets/images/caphe.png", "Coffee & Tea"]
                                        ];

                                        $stmt = $mysqli->prepare("INSERT INTO products(name,description,price,image,category) VALUES (?,?,?,?,?)");
                                        if($stmt){
                                            foreach($products as $p){
                                                $stmt->bind_param("ssiss", $p[0], $p[1], $p[2], $p[3], $p[4]);
                                                if(!$stmt->execute()){
                                                    $err = "Failed to insert product: ".$stmt->error;
                                                    break;
                                                }
                                            }
                                            $stmt->close();
                                        } else {
                                            $err = "Failed to prepare products statement: ".$mysqli->error;
                                        }
                                    }

                                    // GENERATE config.php
                                    if(!isset($err)){
                                        $config =
"<?php
// config.php generated automatically
\$DB_HOST = '".addslashes($host)."';
\$DB_USER = '".addslashes($user)."';
\$DB_PASS = '".addslashes($pass)."';
\$DB_NAME = '".addslashes($name)."';

function db_connect(){
    global \$DB_HOST,\$DB_USER,\$DB_PASS,\$DB_NAME;
    \$db = new mysqli(\$DB_HOST,\$DB_USER,\$DB_PASS,\$DB_NAME);
    if(\$db->connect_errno){
        error_log('DB connect error: '.\$db->connect_error);
        return null;
    }
    \$db->set_charset('utf8mb4');
    return \$db;
}
?>";

                                        $configDir = __DIR__.'/app/config';
                                        if(!is_dir($configDir)){
                                            if(!mkdir($configDir, 0755, true)){
                                                $err = "Failed to create config directory";
                                            }
                                        }
                                        
                                        if(!isset($err)){
                                            if(!file_put_contents($configDir.'/config.php', $config)){
                                                $err = "Failed to write config.php file";
                                            } else {
                                                // Verify database was created and has tables
                                                $verifyQuery = "SHOW TABLES";
                                                $verifyResult = $mysqli->query($verifyQuery);
                                                if($verifyResult && $verifyResult->num_rows >= 4){
                                                    $success = true;
                                                } else {
                                                    $err = "Database created but verification failed. Tables found: ".($verifyResult ? $verifyResult->num_rows : 0);
                                                }
                                            }
                                        }
                                    }
                                    
                                    $mysqli->close();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Installer</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
    .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
    label { display: block; margin: 10px 0 5px 0; font-weight: bold; }
    input { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; }
    button { background: #6e3dd7; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    button:hover { background: #5a2fb8; }
</style>
</head>
<body>
<h1>Rau Câu Gengar Installer</h1>

<?php
    if(isset($err)) echo "<div class='error'><strong>Error:</strong> $err</div>";
    if(isset($success)) echo "<div class='success'><strong>Installation complete!</strong><br>Admin: <b>admin / admin123</b></div>
    <p><a href='/raucau/public/'>Go to Website</a> | <a href='/raucau/public/admin/login'>Admin Panel</a></p>";
?>

<form method="post">
    <h3>Database Settings</h3>
    <label>Host</label>
    <input name="db_host" value="127.0.0.1" required>
    
    <label>User</label>
    <input name="db_user" value="root" required>
    
    <label>Password</label>
    <input name="db_pass" type="password" value="">
    
    <label>Database Name</label>
    <input name="db_name" value="raucaushop" required>
    
    <br>
    <button type="submit">Install</button>
</form>

<p><b>Delete install.php after installing for security.</b></p>

</body>
</html>
