<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temperature Readings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .container {
            max-width: 90%;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            font-size: 1.5rem;
            color: #444;
            text-align: center;
        }
        .temperature {
            margin-bottom: 20px;
        }
        .temperature p {
            font-size: 1.1rem;
            margin: 5px 0;
        }
        .location {
            font-weight: bold;
            color: #0056b3;
        }
        .temp-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff5722;
        }
        .time {
            color: #666;
            font-size: 0.9rem;
        }
        .outdated .time {
            color: red;
            font-weight: bold;
        }
        #officeChart {
            width: 100%;
            height: 300px;
            margin: 20px 0;
        }
        @media(min-width: 768px) {
            h1, h2 {
                font-size: 2rem;
            }
            .container {
                max-width: 70%;
            }
            #officeChart {
                height: 500px;
            }
        }
    </style>
    <script>
        function autoRefresh() {
            window.location = window.location.href;
        }
        setInterval('autoRefresh()', 180000);
    </script>

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script>
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawOfficeChart);

        function drawOfficeChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('date', 'Time');
            data.addColumn('number', 'Office');
            data.addColumn('number', 'Ground Floor');
            data.addColumn('number', 'Upstairs');
            data.addColumn('number', 'MeteoFrance - Outside');

            data.addRows([
                <?php
                # PHP code to fetch data from the database and output JavaScript-friendly data format

                # Load database configuration from *.ini-file
                $ini = parse_ini_file('cfg/db_config.ini');
                $db_host = $ini['db_host'];
                $db_name = $ini['db_name'];
                $db_table = $ini['db_table'];
                $db_user = $ini['db_user'];
                $db_password = $ini['db_password'];

                # Connect to MySQL database
                $connection = new mysqli($db_host, $db_user, $db_password, $db_name);

                if ($connection->connect_error) {
                    die("Connection failed: " . $connection->connect_error);
                }

                # Query the last 4320 readings from the database
                $sql = "SELECT sensor, dt, temp FROM $db_table ORDER BY id DESC LIMIT 4320";
                $result = $connection->query($sql);

                while ($row = $result->fetch_assoc()) {
                    // Create DateTime object from the string, specifying it as UTC
                    $myDateTime = new DateTime($row['dt'], new DateTimeZone('UTC'));
                    // Convert the DateTime object to French time (Europe/Paris)
                    $myDateTime->setTimezone(new DateTimeZone('Europe/Paris'));

                    // Get the timestamp in milliseconds for the chart
                    $myTimestamp = $myDateTime->getTimestamp() * 1000;  // Convert to milliseconds for JavaScript

                    $temperature = round($row['temp'], 1);

                    if ($row['temp'] == 85) {
                        echo "[new Date($myTimestamp), null, null, null, null],";
                    } else {
                        if ($row['sensor'] == '28195d1f37200133') {
                            echo "[new Date($myTimestamp), $temperature, null, null, null],";
                        } elseif ($row['sensor'] == '28d9b516372001db') {
                            echo "[new Date($myTimestamp), null, $temperature, null, null],";
                        } elseif ($row['sensor'] == '28ee581437200182') {
                            echo "[new Date($myTimestamp), null, null, $temperature, null],";
                        } elseif ($row['sensor'] == 'meteofrance') {
                            echo "[new Date($myTimestamp), null, null, null, $temperature],";
                        }
                    }
                }

                ?>
            ]);

            var options = {
                title: 'Temperature Readings',
                width: '100%',
                height: '100%',
                interpolateNulls: true,
                lineWidth: 2,
                colors: ['#4285F4', '#DB4437', '#F4B400', '#61D800'],
                hAxis: {
                    title: 'Time',
                    format: 'HH:mm',
                    gridlines: {count: 10}
                },
                vAxis: {
                    title: 'Temperature (°C)',
                    gridlines: {count: 10}
                },
                legend: {position: 'bottom'},
                chartArea: {width: '80%', height: '70%'}
            };

            var chart = new google.visualization.LineChart(document.getElementById('officeChart'));
            chart.draw(data, options);
        }

        window.addEventListener('resize', drawOfficeChart);  // Redraw the chart on window resize
    </script>
</head>
<body>
    <div class="container">
        <h2>Temperature Chart</h2>
        <div id="officeChart"></div>
        <hr>
        <h1>Current Temperatures</h1>
        <?php
        # Get the most recent temperature data for display

        $sentence = '';

        # Query for each sensor
        $sensors = [
            '28ee581437200182' => 'Upstairs',
            '28d9b516372001db' => 'On the ground floor',
            '28195d1f37200133' => 'In the office',
            'meteofrance' => 'And outside (according to Meteo France)'
        ];

        foreach ($sensors as $sensorId => $location) {
            $sql = "SELECT dt, temp FROM $db_table WHERE sensor='$sensorId' ORDER BY id DESC LIMIT 1";
            $display = $connection->query($sql);

            if ($display->num_rows > 0) {
                while ($row = $display->fetch_assoc()) {
                    $timedt = date_create($row['dt'], timezone_open('UTC'));
                    $ti = date_timezone_set($timedt, timezone_open('Europe/Paris'));
                    $current_time = new DateTime("now", new DateTimeZone('Europe/Paris'));

                    # Check if the data is older than 1 hour
                    $is_outdated = $current_time->getTimestamp() - $timedt->getTimestamp() > 3600;

                    # Assign a class for outdated data
                    $outdated_class = $is_outdated ? 'outdated' : '';

                    $sentence .= "<div class='temperature $outdated_class'>
                                    <p class='location'>$location</p>
                                    <p class='temp-value'>".round($row['temp'], 1)."°C</p>
                                    <span class='time'>at ".date_format($ti, 'd-m-Y H:i')."</span>
                                 </div>";
                }
            }
        }

        echo $sentence;
        ?>
    </div>
</body>
</html>
