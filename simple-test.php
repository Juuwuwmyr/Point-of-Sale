<?php
echo "Testing PDO MySQL...\n";
if (extension_loaded('pdo_mysql')) {
    echo "PDO MySQL extension: LOADED\n";
} else {
    echo "PDO MySQL extension: NOT LOADED\n";
}

if (in_array('mysql', PDO::getAvailableDrivers())) {
    echo "MySQL driver: AVAILABLE\n";
} else {
    echo "MySQL driver: NOT AVAILABLE\n";
}
?>
