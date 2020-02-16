<?php
    define("DEBUG", FALSE);

    define("MAX_SIZE_NUM", 10000);
    define("MIN_SIZE_NUM", -1);
    define("DEFAULT_PREF_COUNT", 6);

// Important: use 127.0.0.1:8889 as the host when running PHPUnit tests.  For regular use,
// use localhost.
//    define("MYSQL_HOST", "127.0.0.1:8889");
    define("MYSQL_HOST", "localhost");
    define("MYSQL_USER", "root");
    define("MYSQL_PASSWD", "password");             // This should be changed in production use.
    define("MYSQL_DB", "camprama_chugbot_db");
    define("MYSQL_PATH", "/usr/local/Cellar/mysql/8.0.19/bin"); // This should be changed in production use.

    define("ADMIN_EMAIL_USERNAME", "brianroth@localhost.com");
    define("ADMIN_EMAIL_PASSWORD", "Roth@96b"); // This should be changed in production use
?>
