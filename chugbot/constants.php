<?php
    define("DEBUG", FALSE);

    define("MAX_SIZE_NUM", 10000);
    define("MIN_SIZE_NUM", -1);
    define("DEFAULT_PREF_COUNT", 6);

// Important: use 127.0.0.1:8889 as the host when running PHPUnit tests.  For regular use,
// use localhost.
//    define("MYSQL_HOST", "127.0.0.1:8889");
    define("MYSQL_HOST", getenv("MYSQL_HOST"));
    define("MYSQL_USER", getenv("MYSQL_USER"));
    define("MYSQL_PASSWD", getenv("MYSQL_PASSWD"));
    define("MYSQL_DB", getenv("MYSQL_DB"));
    define("MYSQL_PATH", "/usr/bin");

    define("EMAIL_HOST", "email-smtp.us-east-2.amazonaws.com");
    define("EMAIL_PORT", 587);
    define("ADMIN_EMAIL_USERNAME", getenv("ADMIN_EMAIL_USERNAME"));
    define("ADMIN_EMAIL_PASSWORD", getenv("ADMIN_EMAIL_PASSWORD"));
?>
