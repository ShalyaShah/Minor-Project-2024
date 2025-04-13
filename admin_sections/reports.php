<?php
// Check if this file is accessed directly
if (!defined('ADMIN_ACCESS')) {
    // If accessed via AJAX from admin_dashboard.php, this will be defined
    define('ADMIN_ACCESS', true);
}

// Check for admin session if accessed directly
if (!isset($_SESSION) || !isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        echo "Unauthorized access";
        exit();
    }
}

// Database connection
$conn = new mysqli("localhost", "root", "", "minor-project");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get report parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'bookings';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Prepare date condition for SQL queries
$date_condition = "";
if ($report_type == 'bookings') {
    $date_condition = " WHERE booking_date BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'";
} elseif ($report_type == 'revenue') {
    $date_condition = " WHERE booking_date BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'";
} elseif ($report_type == 'passengers') {
    // For passengers, we need to join with flight_booked to get the booking date
    $date_condition = " WHERE fb.booking_date BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'";
}

// Get report data based on type
$report_data = [];
$chart_data = [];

if ($report_type == 'bookings') {
    // Get total bookings
    $total_query = "SELECT COUNT(*) as total FROM flight_booked" . $date_condition;
    $total_result = $conn->query($total_query);
    $total_bookings = $total_result->fetch_assoc()['total'];
    
    // Get bookings by date
    $bookings_by_date_query = "SELECT DATE(booking_date) as date, COUNT(*) as count 
                              FROM flight_booked" . $date_condition . "
                              GROUP BY DATE(booking_date) 
                              ORDER BY date";
    $bookings_by_date_result = $conn->query($bookings_by_date_query);
    
    while ($row = $bookings_by_date_result->fetch_assoc()) {
        $chart_data[] = [
            'date' => $row['date'],
            'count' => (int)$row['count']
        ];
    }
    
    // Get top routes
    $top_routes_query = "SELECT origin_name, destination_name, COUNT(*) as count 
                        FROM flight_booked" . $date_condition . "
                        GROUP BY origin_name, destination_name 
                        ORDER BY count DESC 
                        LIMIT 5";
    $top_routes_result = $conn->query($top_routes_query);
    
    $top_routes = [];
    while ($row = $top_routes_result->fetch_assoc()) {
        $top_routes[] = [
            'route' => $row['origin_name'] . ' to ' . $row['destination_name'],
            'count' => (int)$row['count']
        ];
    }
    
    $report_data = [
        'total_bookings' => $total_bookings,
        'chart_data' => $chart_data,
        'top_routes' => $top_routes
    ];
} elseif ($report_type == 'revenue') {
    // Get total revenue
    $total_query = "SELECT SUM(total_amount) as total FROM flight_booked" . $date_condition;
    $total_result = $conn->query($total_query);
    $total_revenue = $total_result->fetch_assoc()['total'] ?: 0;
    
    // Get revenue by date
    $revenue_by_date_query = "SELECT DATE(booking_date) as date, SUM(total_amount) as amount 
                             FROM flight_booked" . $date_condition . "
                             GROUP BY DATE(booking_date) 
                             ORDER BY date";
    $revenue_by_date_result = $conn->query($revenue_by_date_query);
    
    while ($row = $revenue_by_date_result->fetch_assoc()) {
        $chart_data[] = [
            'date' => $row['date'],
            'amount' => (float)$row['amount']
        ];
    }
    
    // Get top revenue routes
    $top_routes_query = "SELECT origin_name, destination_name, SUM(total_amount) as amount 
                        FROM flight_booked" . $date_condition . "
                        GROUP BY origin_name, destination_name 
                        ORDER BY amount DESC 
                        LIMIT 5";
    $top_routes_result = $conn->query($top_routes_query);
    
    $top_routes = [];
    while ($row = $top_routes_result->fetch_assoc()) {
        $top_routes[] = [
            'route' => $row['origin_name'] . ' to ' . $row['destination_name'],
            'amount' => (float)$row['amount']
        ];
    }
    
    $report_data = [
        'total_revenue' => $total_revenue,
        'chart_data' => $chart_data,
        'top_routes' => $top_routes
    ];
} elseif ($report_type == 'passengers') {
    // Get total passengers
    $total_query = "SELECT COUNT(*) as total FROM flight_passenger_info fpi 
                   JOIN flight_booked fb ON fpi.booking_reference = fb.booking_reference" . $date_condition;
    $total_result = $conn->query($total_query);
    $total_passengers = $total_result->fetch_assoc()['total'];
    
    // Get passengers by type
    $passengers_by_type_query = "SELECT passenger_type, COUNT(*) as count 
                                FROM flight_passenger_info fpi 
                                JOIN flight_booked fb ON fpi.booking_reference = fb.booking_reference" . $date_condition . "
                                GROUP BY passenger_type";
    $passengers_by_type_result = $conn->query($passengers_by_type_query);
    
    $passengers_by_type = [];
    while ($row = $passengers_by_type_result->fetch_assoc()) {
        $passengers_by_type[] = [
            'type' => ucfirst($row['passenger_type']),
            'count' => (int)$row['count']
        ];
    }
    
    // Get passengers by date
    $passengers_by_date_query = "SELECT DATE(fb.booking_date) as date, COUNT(*) as count 
                                FROM flight_passenger_info fpi 
                                JOIN flight_booked fb ON fpi.booking_reference = fb.booking_reference" . $date_condition . "
                                GROUP BY DATE(fb.booking_date) 
                                ORDER BY date";
    $passengers_by_date_result = $conn->query($passengers_by_date_query);
    
    while ($row = $passengers_by_date_result->fetch_assoc()) {
        $chart_data[] = [
            'date' => $row['date'],
            'count' => (int)$row['count']
        ];
    }
    
    $report_data = [
        'total_passengers' => $total_passengers,
        'passengers_by_type' => $passengers_by_type,
        'chart_data' => $chart_data
    ];
}

// Convert chart data to JSON for JavaScript
$chart_data_json = json_encode($chart_data);
?>

<div class="section-header">
    <h2>Reports & Analytics</h2>
</div>

<div class="reports-filters">
    <form id="report-filter-form" class="filter-form">
        <div class="filter-group">
            <label for="report-type">Report Type:</label>
            <select id="report-type" name="report_type">
                <option value="bookings" <?php echo $report_type == 'bookings' ? 'selected' : ''; ?>>Bookings</option>
                <option value="revenue" <?php echo $report_type == 'revenue' ? 'selected' : ''; ?>>Revenue</option>
                <option value="passengers" <?php echo $report_type == 'passengers' ? 'selected' : ''; ?>>Passengers</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="date-from">From:</label>
            <input type="date" id="date-from" name="date_from" value="<?php echo $date_from; ?>">
        </div>
        <div class="filter-group">
            <label for="date-to">To:</label>
            <input type="date" id="date-to" name="date_to" value="<?php echo $date_to; ?>">
        </div>
        <button type="submit" class="filter-btn">Generate Report</button>
        <button type="button" id="export-report" class="export-btn">Export to CSV</button>
    </form>
</div>

<div class="report-container">
    <?php if ($report_type == 'bookings'): ?>
        <div class="report-summary">
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="summary-content">
                    <h3>Total Bookings</h3>
                    <p class="summary-value"><?php echo number_format($report_data['total_bookings']); ?></p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="summary-content">
                    <h3>Daily Average</h3>
                    <?php
                    $days = max(1, (strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24));
                    $daily_avg = $report_data['total_bookings'] / $days;
                    ?>
                    <p class="summary-value"><?php echo number_format($daily_avg, 1); ?></p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="summary-content">
                    <h3>Period</h3>
                    <p class="summary-value"><?php echo date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to)); ?></p>
                </div>
            </div>
        </div>
        
        <div class="report-charts">
            <div class="chart-container">
                <h3>Bookings Over Time</h3>
                <canvas id="bookings-chart"></canvas>
            </div>
            
            <div class="chart-container">
                <h3>Top Routes</h3>
                <?php if (!empty($report_data['top_routes'])): ?>
                    <div class="top-routes">
                        <?php foreach ($report_data['top_routes'] as $route): ?>
                            <div class="route-item">
                                <div class="route-name"><?php echo $route['route']; ?></div>
                                <div class="route-bar-container">
                                    <div class="route-bar" style="width: <?php echo min(100, ($route['count'] / $report_data['top_routes'][0]['count']) * 100); ?>%"></div>
                                </div>
                                <div class="route-count"><?php echo $route['count']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data">No route data available for the selected period.</p>
                <?php endif; ?>
            </div>
        </div>
        
    <?php elseif ($report_type == 'revenue'): ?>
        <div class="report-summary">
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="summary-content">
                    <h3>Total Revenue</h3>
                    <p class="summary-value">₹<?php echo number_format($report_data['total_revenue'], 2); ?></p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="summary-content">
                    <h3>Daily Average</h3>
                    <?php
                    $days = max(1, (strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24));
                    $daily_avg = $report_data['total_revenue'] / $days;
                    ?>
                    <p class="summary-value">₹<?php echo number_format($daily_avg, 2); ?></p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="summary-content">
                    <h3>Period</h3>
                    <p class="summary-value"><?php echo date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to)); ?></p>
                </div>
            </div>
        </div>
        
        <div class="report-charts">
            <div class="chart-container">
                <h3>Revenue Over Time</h3>
                <canvas id="revenue-chart"></canvas>
            </div>
            
            <div class="chart-container">
                <h3>Top Revenue Routes</h3>
                <?php if (!empty($report_data['top_routes'])): ?>
                    <div class="top-routes">
                        <?php foreach ($report_data['top_routes'] as $route): ?>
                            <div class="route-item">
                                <div class="route-name"><?php echo $route['route']; ?></div>
                                <div class="route-bar-container">
                                    <div class="route-bar" style="width: <?php echo min(100, ($route['amount'] / $report_data['top_routes'][0]['amount']) * 100); ?>%"></div>
                                </div>
                                <div class="route-count">₹<?php echo number_format($route['amount'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data">No revenue data available for the selected period.</p>
                <?php endif; ?>
            </div>
        </div>
        
    <?php elseif ($report_type == 'passengers'): ?>
        <div class="report-summary">
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="summary-content">
                    <h3>Total Passengers</h3>
                    <p class="summary-value"><?php echo number_format($report_data['total_passengers']); ?></p>
                </div>
            </div>
            
            <?php foreach ($report_data['passengers_by_type'] as $type): ?>
                <div class="summary-card">
                    <div class="summary-icon">
                        <?php if ($type['type'] == 'Adult'): ?>
                            <i class="fas fa-user"></i>
                        <?php elseif ($type['type'] == 'Child'): ?>
                            <i class="fas fa-child"></i>
                        <?php else: ?>
                            <i class="fas fa-baby"></i>
                        <?php endif; ?>
                    </div>
                    <div class="summary-content">
                        <h3><?php echo $type['type']; ?> Passengers</h3>
                        <p class="summary-value"><?php echo number_format($type['count']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="report-charts">
            <div class="chart-container">
                <h3>Passengers Over Time</h3>
                <canvas id="passengers-chart"></canvas>
            </div>
            
            <div class="chart-container">
                <h3>Passenger Types Distribution</h3>
                <canvas id="passenger-types-chart"></canvas>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .reports-filters {
        margin-bottom: 20px;
    }
    
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
        align-items: center;
    }
    
    .filter-group {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .filter-group label {
        font-size: 14px;
        color: var(--secondary-color);
    }
    
    .filter-group select, .filter-group input {
        padding: 8px 10px;
        border: 1px solid #eee;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .filter-btn, .export-btn {
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
    }
    
    .filter-btn {
        background-color: var(--primary-color);
        color: white;
    }
    
    .export-btn {
        background-color: var(--success-color);
        color: white;
    }
    
    .report-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .summary-card {
        display: flex;
        align-items: center;
        padding: 20px;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .summary-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(74, 108, 247, 0.1);
        color: var(--primary-color);
        border-radius: 10px;
        font-size: 20px;
        margin-right: 15px;
    }
    
    .summary-content h3 {
        margin: 0 0 5px 0;
        font-size: 14px;
        color: var(--secondary-color);
    }
    
    .summary-value {
        margin: 0;
        font-size: 24px;
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .report-charts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
    }
    
    .chart-container {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 20px;
    }
    
    .chart-container h3 {
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 16px;
        color: var(--dark-color);
    }
    
    canvas {
        width: 100% !important;
        height: 300px !important;
    }
    
    .top-routes {
        margin-top: 20px;
    }
    
    .route-item {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .route-name {
        width: 30%;
        font-size: 14px;
        color: var(--dark-color);
    }
    
    .route-bar-container {
        flex: 1;
        height: 10px;
        background-color: #f1f1f1;
        border-radius: 5px;
        margin: 0 15px;
    }
    
    .route-bar {
        height: 100%;
        background-color: var(--primary-color);
        border-radius: 5px;
    }
    
    .route-count {
        width: 15%;
        text-align: right;
        font-weight: 500;
        color: var(--dark-color);
    }
    
    @media (max-width: 768px) {
        .report-charts {
            grid-template-columns: 1fr;
        }
        
        .route-name {
            width: 40%;
        }
        
        .route-count {
            width: 20%;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Initialize charts based on report type
        const reportType = '<?php echo $report_type; ?>';
        const chartData = <?php echo $chart_data_json; ?>;
        
        if (reportType === 'bookings') {
            initBookingsChart(chartData);
        } else if (reportType === 'revenue') {
            initRevenueChart(chartData);
        } else if (reportType === 'passengers') {
            initPassengersChart(chartData);
            initPassengerTypesChart(<?php echo json_encode($report_data['passengers_by_type']); ?>);
        }
        
        // Report filter form submission
        $("#report-filter-form").submit(function(e) {
            e.preventDefault();
            
            const reportType = $("#report-type").val();
            const dateFrom = $("#date-from").val();
            const dateTo = $("#date-to").val();
            
            loadReportWithFilters(reportType, dateFrom, dateTo);
        });
        
        // Export report to CSV
        $("#export-report").click(function() {
            const reportType = $("#report-type").val();
            const dateFrom = $("#date-from").val();
            const dateTo = $("#date-to").val();
            
            window.location.href = `admin_ajax/export_report.php?report_type=${reportType}&date_from=${dateFrom}&date_to=${dateTo}`;
        });
    });
    
    // Function to load report with filters
    function loadReportWithFilters(reportType, dateFrom, dateTo) {
        $("#reports-section").html('<div class="loading">Generating report...</div>');
        
        $.ajax({
            url: "admin_sections/reports.php",
            data: {
                report_type: reportType,
                date_from: dateFrom,
                date_to: dateTo
            },
            success: function(response) {
                $("#reports-section").html(response);
            },
            error: function() {
                $("#reports-section").html('<div class="error">Failed to generate report.</div>');
            }
        });
    }
    
    // Initialize bookings chart
    function initBookingsChart(data) {
        const ctx = document.getElementById('bookings-chart').getContext('2d');
        
        const labels = data.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        
        const counts = data.map(item => item.count);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Number of Bookings',
                    data: counts,
                    backgroundColor: 'rgba(74, 108, 247, 0.1)',
                    borderColor: 'rgba(74, 108, 247, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
    
    // Initialize revenue chart
    function initRevenueChart(data) {
        const ctx = document.getElementById('revenue-chart').getContext('2d');
        
        const labels = data.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        
        const amounts = data.map(item => item.amount);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: amounts,
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Initialize passengers chart
    function initPassengersChart(data) {
        const ctx = document.getElementById('passengers-chart').getContext('2d');
        
        const labels = data.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        
        const counts = data.map(item => item.count);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Number of Passengers',
                    data: counts,
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    borderColor: 'rgba(23, 162, 184, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
    
    // Initialize passenger types chart
    function initPassengerTypesChart(data) {
        const ctx = document.getElementById('passenger-types-chart').getContext('2d');
        
        const labels = data.map(item => item.type);
        const counts = data.map(item => item.count);
        
        const colors = [
            'rgba(74, 108, 247, 0.7)',
            'rgba(255, 193, 7, 0.7)',
            'rgba(23, 162, 184, 0.7)'
        ];
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: colors,
                    borderColor: colors.map(color => color.replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }
</script>