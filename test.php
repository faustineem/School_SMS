<?php
$password = "admin123";
$hash = "$2y$10$TBV9SJoZkqAE9VsbbmLnhuJV17pbWImlou0vtzKxwp2Yhk3TjhKeG"; // From admin user
if (password_verify($password, $hash)) {
    echo "Password is correct!";
} else {
    echo "Password is incorrect!";
}
?>