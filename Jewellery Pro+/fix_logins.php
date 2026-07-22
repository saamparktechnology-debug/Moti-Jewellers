<?php
$conn = mysqli_connect('127.0.0.1', 'root', 'RootPaas123', 'moti');
$hash = password_hash('123456', PASSWORD_DEFAULT);
$users = [
    ['motijewellers9635985848@gmail.com', '9635985848'],
    ['saamparktechnology@gmail.com', '8617536679'],
    ['hiisupriya@gmail.com', '9876543210']
];
foreach($users as $u) {
    mysqli_query($conn, "UPDATE users SET password='$hash' WHERE email='{$u[0]}' OR mobile='{$u[1]}'");
    if (mysqli_affected_rows($conn) <= 0) {
        mysqli_query($conn, "INSERT IGNORE INTO users (name, mobile, email, password) VALUES ('Admin', '{$u[1]}', '{$u[0]}', '$hash')");
    }
}
echo "All admin users added and passwords updated to 123456 successfully!\n";
?>
