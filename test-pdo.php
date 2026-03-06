<?php
echo "Available PDO Drivers:\n";
print_r(PDO::getAvailableDrivers());

echo "\n\nPHP Version: " . phpversion();
echo "\nPDO Version: " . phpversion('pdo');
echo "\nMySQL PDO Version: " . phpversion('pdo_mysql');
echo "\nLoaded php.ini: " . php_ini_loaded_file();
echo "\nExtension dir: " . ini_get('extension_dir');
echo "\n\nLoaded extensions (first 20):\n";
$ext = get_loaded_extensions();
sort($ext);
print_r(array_slice($ext, 0, 20));
echo "\n\npdo_mysql loaded? " . (extension_loaded('pdo_mysql') ? 'yes' : 'no');
echo "\nmysqli loaded? " . (extension_loaded('mysqli') ? 'yes' : 'no');

// Check if extensions are actually being loaded
echo "\n\nChecking if PDO MySQL is actually usable:\n";
try {
    if (class_exists('PDO')) {
        echo "PDO class exists: YES\n";
        if (in_array('mysql', PDO::getAvailableDrivers(), true)) {
            echo "MySQL driver available: YES\n";
            // Try to create a PDO connection to test
            $test = new PDO('mysql:host=localhost;dbname=test', 'root', '');
            echo "PDO MySQL connection test: " . ($test ? 'SUCCESS' : 'FAILED') . "\n";
        } else {
            echo "MySQL driver available: NO\n";
        }
    } else {
        echo "PDO class exists: NO\n";
    }
} catch (Exception $e) {
    echo "Error testing PDO: " . $e->getMessage() . "\n";
}
?>
