<?php

/**
 * Example usage of SoapGps class
 */

#
# REQUIRE SOURCE CODE
#

require_once 'soap-lib.php';

#
# CONFIGURATION
#

// aka login
$username = 'someone@company.com';
// password
$password = 'password';

// tracking this user
$user = '447771957415';

// multiple users request
$users = array(
    'Peter' => $user,
    'Vince' => $user
);

// setting this frequency
$frequency = 60;
// setting this expiration
$expiration = 600;


#
# USAGE
#

// embed the code into try to make sure all results are without errors / exceptions
try {
    // create object
    $gpssoap = new GpsSoap();

    // login
    $rs = $gpssoap->login($username, $password);

    echo 'LOGIN RESULT IS ' . PHP_EOL;
    var_dump($rs);
    echo PHP_EOL . PHP_EOL;

    // get last position for user
    $position = $gpssoap->getLastPosition($user);

    echo 'GETTING LAST POSITION FOR USER ' . $user . ':' . PHP_EOL;
    var_dump($position);
    echo PHP_EOL . PHP_EOL;

    // get last position for multiple users
    $positions = $gpssoap->getLastPositions($users);

    echo 'GETTING LAST POSITION FOR USERS ' . implode(', ', $users) . ':' . PHP_EOL;
    var_dump($positions);
    echo PHP_EOL . PHP_EOL;

    foreach($positions as $name => $status) {
        echo 'Username: ' . $name . PHP_EOL;
        echo 'Position: ' . $status['longitude'] . '/' . $status['latitude'] . PHP_EOL;
        echo 'Time: ' . date('Y-m-d H:i:s', $status['timestamp']) . PHP_EOL;
    }

    // set tracking frequency
    echo 'SETTING TRACKING FREQUENCY ' . $frequency . ', EXPIRATION ' . $expiration . ' FOR USER ' . $user . ':' . PHP_EOL;
    $rs = $gpssoap->setTrackingFrequencyRequest($user, $frequency, $expiration);
    var_dump($rs);
    echo PHP_EOL . PHP_EOL;

    // logut
    echo 'LOGGING OUT: ' . PHP_EOL;
    $rs = $gpssoap->logout();
    var_dump($rs);
    echo PHP_EOL . PHP_EOL;

    echo 'DONE' . PHP_EOL;

} catch (Exception $e) {
    die('Fatal error: ' . $e->getMessage());
}
