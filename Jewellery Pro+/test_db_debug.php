<?php
require 'config/database.php';
$search = 'hiisupriya@gmail.com';
$check = mysqli_query($conn, "SELECT id, name, email, mobile FROM users WHERE email = '$search'");
if($check && mysqli_num_rows($check) > 0) {
    echo 'FOUND ' . mysqli_fetch_assoc($check)['email'] . ' in ' . $db_name;
} else {
    echo 'NOT FOUND. Database: ' . $db_name . ' Error: ' . mysqli_error($conn);
}
// Show all users
$q = mysqli_query($conn, "SELECT email FROM users");
while($r = mysqli_fetch_assoc($q)) {
    echo " | " . $r['email'];
}
