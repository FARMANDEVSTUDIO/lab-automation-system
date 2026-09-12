<?php
declare(strict_types=1);
// Registration redirect — routes to signup flow (Naveera)
require_once __DIR__ . '/../config/config.php';

header('Location: ' . BASE_URL . '/auth/signup.php');
exit;
