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

    define("EMAIL_HOST", getenv("EMAIL_HOST"));
    define("EMAIL_PORT", getenv("EMAIL_PORT"));
    define("ADMIN_EMAIL_USERNAME", getenv("EMAIL_USERNAME"));
    define("ADMIN_EMAIL_PASSWORD", getenv("EMAIL_PASSWORD"));
?>
