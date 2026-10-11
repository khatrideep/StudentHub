<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_logout();
auth_redirect('login.html?logout=1');
