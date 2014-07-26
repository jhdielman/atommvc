<?php

/**
 * AtomMVC: Database Configuration
 * atom/config/db.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 * 
 */

return [
    // The database server name or IP address. Usually this is 'localhost' or '127.0.0.1'.
    'server' => 'localhost',

    // The database username to connect with.
    'user' => 'DB_USER',

    // The database password to connect with.
    'password' => 'DB_PASS',

    // The name of the database to select.
    'database' => 'DB_NAME',

    // The prefix to use when naming tables. This can be no more than 5 characters.
    'tablePrefix' => '',
];