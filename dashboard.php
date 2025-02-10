<?php
// Error handling for log file fetching
function fetchLogFile($url) {
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'ignore_errors' => true,
                'header' => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]
        ]);
        
        $logData = @file_get_contents($url, false, $context);
        
        if ($logData === false) {
            throw new Exception("Failed to fetch log file");
        }
        
        return explode("\n", $logData);
    } catch (Exception $e) {
        return [];
    }
}

function parseLogData($logLines) {
    $ipCounter = [];
    $hourCounter = [];
    $browserStats = [];
    $requestTypes = [];
    $statusCodes = [];
    $datewiseTraffic = [];
    
    foreach ($logLines as $line) {
        if (preg_match('/(?P<ip>\d+\.\d+\.\d+\.\d+).* \[(?P<date>\d{2}\/[A-Za-z]+\/\d{4}):(?P<hour>\d{2}):\d{2}:\d{2}.*] "(?P<request>.*?)" (?P<status>\d+) \d+ "(?P<referer>.*?)" "(?P<useragent>.*?)"/', $line, $matches)) {
            $ip = $matches['ip'];
            $hour = $matches['hour'];
            $date = $matches['date'];
            $request = explode(' ', $matches['request'])[0];
            $status = $matches['status'];
            
            // Count IPs
            $ipCounter[$ip] = ($ipCounter[$ip] ?? 0) + 1;
            
            // Count Hours
            $hourCounter[$hour] = ($hourCounter[$hour] ?? 0) + 1;
            
            // Count Request Types
            $requestTypes[$request] = ($requestTypes[$request] ?? 0) + 1;
            
            // Count Status Codes
            $statusCodes[$status] = ($statusCodes[$status] ?? 0) + 1;
            
            // Count Datewise Traffic
            $datewiseTraffic[$date] = ($datewiseTraffic[$date] ?? 0) + 1;
            
            // Enhanced browser detection
            if (strpos($matches['useragent'], 'Chrome') !== false && strpos($matches['useragent'], 'Edge') === false) {
                $browser = 'Chrome';
            } elseif (strpos($matches['useragent'], 'Firefox') !== false) {
                $browser = 'Firefox';
            } elseif (strpos($matches['useragent'], 'Safari') !== false && strpos($matches['useragent'], 'Chrome') === false) {
                $browser = 'Safari';
            } elseif (strpos($matches['useragent'], 'Edge') !== false) {
                $browser = 'Edge';
            } else {
                $browser = 'Others';
            }
            $browserStats[$browser] = ($browserStats[$browser] ?? 0) + 1;
        }
    }
    
    return [
        'ipCounter' => $ipCounter,
        'hourCounter' => $hourCounter,
        'browserStats' => $browserStats,
        'requestTypes' => $requestTypes,
        'statusCodes' => $statusCodes,
        'datewiseTraffic' => $datewiseTraffic
    ];
}

function topContributors($counter, $percentage) {
    $total = array_sum($counter);
    $threshold = $total * ($percentage / 100);
    arsort($counter);
    
    $cumulative = 0;
    $topItems = [];
    
    foreach ($counter as $key => $count) {
        $cumulative += $count;
        $topItems[$key] = $count;
        if ($cumulative >= $threshold) {
            break;
        }
    }
    
    return $topItems;
}

function formatNumber($number) {
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    return $number;
}

// Fetch and process data
$logFileUrl = "https://support.netgables.org/apache_combined.log";
$logLines = fetchLogFile($logFileUrl);
$stats = parseLogData($logLines);

// Calculate derived statistics
$totalVisits = array_sum($stats['ipCounter']);
$uniqueVisitors = count($stats['ipCounter']);
$topIps = topContributors($stats['ipCounter'], 85);
$topHours = topContributors($stats['hourCounter'], 70);
$successRate = isset($stats['statusCodes']['200']) ? 
    round(($stats['statusCodes']['200'] / array_sum($stats['statusCodes'])) * 100, 1) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Log Analysis Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #818cf8;
            --success: #10b981;
            --info: #3b82f6;
            --warning: #f59e0b;
            --danger: #ef4444;
            --background: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
        }

        body {
            background-color: var(--background);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--text-primary);
            line-height: 1.5;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 3rem 0;
            margin-bottom: 2.5rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .card {
            background: var(--card-bg);
            border-radius: 1rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            padding: 1.5rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .stat-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
        }

        .table tbody tr:hover {
            background-color: rgba(79, 70, 229, 0.05);
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: var(--border-color);
        }

        .progress-bar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 4px;
        }

        .chart-container {
            position: relative;
            min-height: 300px;
            padding: 1rem;
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
        }

        .card-title {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1.125rem;
            margin: 0;
        }

        .data-table td {
            vertical-align: middle;
            padding: 1rem;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                padding: 2rem 0;
            }
            
            .stat-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <div class="container">
            <h1 class="text-center mb-2">
                <i class="fas fa-chart-line me-2"></i>
                Log Analysis Dashboard
            </h1>
            <p class="text-center text-white-50 mb-0">Real-time server analytics and insights</p>
        </div>
    </header>

    <div class="container">
        <!-- Summary Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                    <div class="stat-value"><?php echo formatNumber($totalVisits); ?></div>
                    <div class="stat-label">Total Visits</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check fa-lg"></i>
                    </div>
                    <div class="stat-value"><?php echo formatNumber($uniqueVisitors); ?></div>
                    <div class="stat-label">Unique Visitors</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                    <div class="stat-value"><?php echo array_search(max($stats['hourCounter']), $stats['hourCounter']); ?>:00</div>
                    <div class="stat-label">Peak Hour</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                    <div class="stat-value"><?php echo $successRate; ?>%</div>
                    <div class="stat-label">Success Rate</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Hourly Traffic Distribution</h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Browser Distribution</h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="browserChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Data -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Top IP Addresses (85% of Traffic)</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table data-table">
                            <thead>
                                <tr>
                                    <th>IP Address</th>
                                    <th>Visits</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($topIps as $ip => $count) {
                                    $percentage = round(($count / $totalVisits) * 100, 1);
                                ?>
                                <tr>
                                    <td class="fw-medium"><?php echo $ip; ?></td>
                                    <td><?php echo number_format($count); ?></td>

                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2">
                                                <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <span class="text-muted"><?php echo $percentage; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Peak Hours (70% of Traffic)</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table data-table">
                            <thead>
                                <tr>
                                    <th>Hour</th>
                                    <th>Visitors</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalHourly = array_sum($stats['hourCounter']);
                                foreach ($topHours as $hour => $count) {
                                    $percentage = round(($count / $totalHourly) * 100, 1);
                                ?>
                                <tr>
                                    <td class="fw-medium"><?php echo $hour; ?>:00</td>
                                    <td><?php echo number_format($count); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2">
                                                <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <span class="text-muted"><?php echo $percentage; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Request Types and Status Codes -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">HTTP Request Types</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table data-table">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalRequests = array_sum($stats['requestTypes']);
                                arsort($stats['requestTypes']);
                                foreach ($stats['requestTypes'] as $method => $count) {
                                    $percentage = round(($count / $totalRequests) * 100, 1);
                                ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($method); ?></td>
                                    <td><?php echo number_format($count); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2">
                                                <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <span class="text-muted"><?php echo $percentage; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">HTTP Status Codes</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table data-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalStatus = array_sum($stats['statusCodes']);
                                arsort($stats['statusCodes']);
                                foreach ($stats['statusCodes'] as $status => $count) {
                                    $percentage = round(($count / $totalStatus) * 100, 1);
                                    $statusClass = substr($status, 0, 1) == '2' ? 'text-success' : 
                                                (substr($status, 0, 1) == '4' ? 'text-warning' : 
                                                (substr($status, 0, 1) == '5' ? 'text-danger' : 'text-info'));
                                ?>
                                <tr>
                                    <td class="fw-medium <?php echo $statusClass; ?>"><?php echo $status; ?></td>
                                    <td><?php echo number_format($count); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2">
                                                <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <span class="text-muted"><?php echo $percentage; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="py-4 mt-4">
        <div class="container">
            <p class="text-center text-muted mb-0">Last Updated: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script>
        // Configure Chart.js defaults
        Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";
        Chart.defaults.font.size = 13;
        Chart.defaults.color = '#64748b';
        
        // Prepare data for charts
        const hourlyData = <?php echo json_encode(array_values($stats['hourCounter'])); ?>;
        const hourLabels = <?php echo json_encode(array_map(function($hour) { 
            return $hour . ':00'; 
        }, array_keys($stats['hourCounter']))); ?>;
        
        const browserData = <?php echo json_encode(array_values($stats['browserStats'])); ?>;
        const browserLabels = <?php echo json_encode(array_keys($stats['browserStats'])); ?>;

        // Create Hourly Traffic Chart
        new Chart(document.getElementById('hourlyChart'), {
            type: 'line',
            data: {
                labels: hourLabels,
                datasets: [{
                    label: 'Visitors',
                    data: hourlyData,
                    fill: true,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#4f46e5',
                    pointHoverBackgroundColor: '#4f46e5'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        bodySpacing: 4,
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            padding: 8
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            padding: 8
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // Create Browser Distribution Chart
        new Chart(document.getElementById('browserChart'), {
            type: 'doughnut',
            data: {
                labels: browserLabels,
                datasets: [{
                    data: browserData,
                    backgroundColor: [
                        '#4f46e5',
                        '#10b981',
                        '#f59e0b',
                        '#3b82f6',
                        '#6366f1'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        bodySpacing: 4
                    }
                },
                cutout: '65%'
            }
        });
    </script>
</body>
</html>
