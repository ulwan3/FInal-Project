<?php
// Redirect root-level register requests to the actual script in /auth/
header("Location: /auth/register.php", true, 302);
exit;
?>