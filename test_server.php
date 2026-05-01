<?php
// Quick diagnostic for InfinityFree
echo "<h2>FinPulse - Server Diagnostic</h2>";

echo "<h3>PHP Version</h3>";
echo PHP_VERSION;

echo "<h3>PDO Drivers Available</h3>";
echo "<pre>";
print_r(PDO::getAvailableDrivers());
echo "</pre>";

echo "<h3>SQLite Support</h3>";
echo extension_loaded('pdo_sqlite') ? '✅ SQLite supported' : '❌ SQLite NOT supported';

echo "<h3>MySQL Support</h3>";
echo extension_loaded('pdo_mysql') ? '✅ MySQL supported' : '❌ MySQL NOT supported';

echo "<h3>Directory Writable</h3>";
echo is_writable(__DIR__) ? '✅ Directory is writable' : '❌ Directory is NOT writable';
?>
