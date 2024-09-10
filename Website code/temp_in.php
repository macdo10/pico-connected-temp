<?php
# Load database configuration from *.ini-file
$ini = parse_ini_file('cfg/db_config.ini');
$db_host = $ini['db_host'];
$db_name = $ini['db_name'];
$db_table = $ini['db_table'];
$db_user = $ini['db_user'];
$db_password = $ini['db_password'];

# Connect to MySQL database
$connection = new mysqli($db_host, $db_user, $db_password, $db_name);


if($connection)
    {
        echo "connection succesfull";
    }
    else {
        echo "Error";
    }
echo "now getting GETs";
    $dt = test_input($_GET['dt']);
//    $date = test_input($_GET['date']);
//    $time = test_input($_GET['time']);
    $temp = test_input($_GET['temp']);
    $sensor = test_input($_GET['sensor']);

echo $date;
echo " ";
echo $time;
echo " ";
echo $temp;
echo " ";
echo $sensor;
//converting mf timestamp
if ($sensor == "meteofrance")
    {
        $date = new DateTime($dt);
        $dt = $date->format('Y-m-d H:i:s');
    }

    //    $sql_insert = "insert into temperature (date, time, temp, sensor) values ('$date','$time','$temp','$sensor')";
    $sql_insert = "insert into temperature1 (dt, temp, sensor) values ('$dt','$temp','$sensor')";
echo $sql_insert;
//echo $sql_insert_clean;
mysqli_query($connection, $sql_insert);
echo "got to end";

 function test_input($data)
 {
     $data = trim($data);
     $data = stripslashes($data);
     $data = htmlspecialchars($data);
     return $data;
 }
?>
