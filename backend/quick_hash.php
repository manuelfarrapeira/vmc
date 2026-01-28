<?php
$password = isset($_GET['pass']) ? $_GET['pass'] : 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h3>Hash Generator</h3>";
echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
echo "<p><strong>Hash:</strong></p>";
echo "<textarea style='width:100%; height:80px; font-family:monospace;'>" . $hash . "</textarea>";
echo "<hr>";
echo "<h4>SQL para phpMyAdmin:</h4>";
echo "<textarea style='width:100%; height:100px; font-family:monospace;'>";
echo "UPDATE usuarios SET pass = '$hash' WHERE user = 'admin';";
echo "</textarea>";
echo "<hr>";
?>
