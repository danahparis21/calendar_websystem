<?php
session_start();

include('db.php');

// Turn on error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Create connection if not already established
if (!($conn instanceof PDO)) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "calendar_system";
    $port = "3307";
    

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;port=$port", $username, $password);

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';


// Initialize messages
$role_message = '';
$announcement_message = '';
$response = ['success' => false, 'message' => ''];

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'], $_POST['user_id'])) {
    $userId = intval($_POST['user_id']);
    $newRole = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    if ($stmt->execute([$newRole, $userId])) {
        $role_message = "<div class='alert alert-success'>User role updated successfully!</div>";
        $response = ['success' => true, 'message' => 'User role updated successfully!', 'new_role' => $newRole];

    } else {
        $role_message = "<div class='alert alert-danger'>Failed to update user role.</div>";
        $response = ['success' => false, 'message' => 'Failed to update user role.'];
    }

    // Return JSON response for AJAX requests
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}

// Handle announcement creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_announcement'])) {
    $title = $_POST['announcement_title'];
    $message = $_POST['announcement_message'];
    $expiresAt = $_POST['expires_at'];
    $createdBy = $_SESSION['user_id'];

    $response = ['success' => false, 'message' => ''];

    try {
        $stmt = $conn->prepare("INSERT INTO announcements (title, message, expires_at, created_by) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$title, $message, $expiresAt, $createdBy])) {
            $response = [
                'success' => true, 
                'message' => 'Announcement created successfully!',
                'announcement' => [
                    'id' => $conn->lastInsertId(),
                    'title' => $title,
                    'message' => $message,
                    'expires_at' => $expiresAt
                ]
            ];
        } else {
            $response = ['success' => false, 'message' => 'Failed to create announcement.'];
        }
    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }

    // Return JSON response for AJAX requests
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } else {
        // For non-AJAX requests (shouldn't happen with our JS fix)
        $announcement_message = $response['success'] 
            ? "<div class='alert alert-success'>{$response['message']}</div>"
            : "<div class='alert alert-danger'>{$response['message']}</div>";
    }
}
// Handle announcement deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'], $_POST['announcement_id'])) {
    $announcementId = intval($_POST['announcement_id']);
    $response = ['success' => false, 'message' => ''];

    try {
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        if ($stmt->execute([$announcementId])) {
            $response = [
                'success' => true, 
                'message' => 'Announcement deleted successfully!', 
                'id' => $announcementId
            ];
        } else {
            $response = ['success' => false, 'message' => 'Failed to delete announcement.'];
        }
    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }

    // Return JSON response for AJAX requests
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } else {
        // Fallback for non-AJAX requests
        $announcement_message = $response['success'] 
            ? "<div class='alert alert-success'>{$response['message']}</div>"
            : "<div class='alert alert-danger'>{$response['message']}</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Calendar System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }

        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fc;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #fff;
            width: 250px;
        }

        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            font-weight: 600;
            color: #333;
            padding: 1rem 1.5rem;
            border-left: 3px solid transparent;
        }

        .sidebar .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(78, 115, 223, 0.05);
        }

        .sidebar .nav-link.active {
            color: var(--primary-color);
            border-left: 3px solid var(--primary-color);
            background-color: rgba(78, 115, 223, 0.1);
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            color: inherit;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 250px;
            /* To avoid being overlapped by the sidebar */
            right: 0;
            z-index: 101;
            /* Higher than sidebar */
            height: 60px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            background-color: #fff;
            padding: 0 1.5rem;
        }


        .navbar-brand {
            font-weight: 800;
            color: var(--dark-color);
        }

        .navbar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
        }

        main {
            margin-left: 250px;
            padding-top: 80px;
            /* Still accommodates fixed navbar height */
        }


        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
        }

        .card-body {
            padding: 1.35rem;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }

        .bg-primary {
            background-color: var(--primary-color) !important;
        }

        .bg-success {
            background-color: var(--success-color) !important;
        }

        .bg-info {
            background-color: var(--info-color) !important;
        }

        .bg-warning {
            background-color: var(--warning-color) !important;
        }

        .bg-danger {
            background-color: var(--danger-color) !important;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #3a5bdc;
            border-color: #3a5bdc;
        }

        .page-header {
            border-bottom: 1px solid #e3e6f0;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .table-responsive {
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            background-color: #fff;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            padding: 1rem 1.35rem;
            background-color: #f8f9fc;
            color: var(--dark-color);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .table td {
            padding: 1rem 1.35rem;
            vertical-align: middle;
        }

        .form-control,
        .form-select {
            border-radius: 0.35rem;
            padding: 0.5rem 1rem;
            border: 1px solid #d1d3e2;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .alert {
            border-radius: 0.35rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            /* space between avatar and name */
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
            flex-shrink: 0;
        }


        .filter-container {
            background-color: #fff;
            padding: 1rem;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
        }

        .status-badge {
            min-width: 80px;
            display: inline-block;
            text-align: center;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 0;
            }

            .sidebar-sticky {
                height: auto;
            }

            main {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
        <div class="container-fluid justify-content-between">
            <h1 class="h3 mb-0 text-gray-800">Admin Dashboard</h1>
            <ul class="navbar-nav">
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-info">
                            <div class="user-avatar">
                                <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                            </div>
                            <span
                                class="d-none d-lg-inline text-gray-600 small"><?= strtoupper($_SESSION['username']) ?></span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>
                                Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</body>




<div class="container-fluid">
    <div class="row">
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="sidebar-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-tab="dashboard">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-tab="users">
                            <i class="bi bi-people"></i> Users Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-tab="events">
                            <i class="bi bi-calendar-event"></i> All Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-tab="announcements">
                            <i class="bi bi-megaphone"></i> Announcements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-tab="audit">
                            <i class="bi bi-list-check"></i> Audit Logs
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-content active">
                <div class="page-header">
                    <h1 class="h2"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
                    <p class="lead">Overview of your calendar system</p>
                </div>

                <div class="row">
                    <?php
                    // Get counts for dashboard
                    $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
                    $total_events = $conn->query("SELECT COUNT(*) as count FROM events")->fetch()['count'];
                    $active_events = $conn->query("SELECT COUNT(*) as count FROM events WHERE status != 'cancelled'")->fetch()['count'];
                    $total_announcements = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE expires_at > NOW()")->fetch()['count'];
                    ?>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_users ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people fs-1 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Active Events</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $active_events ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar-check fs-1 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Total Events</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_events ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar-event fs-1 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Active Announcements</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= $total_announcements ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-megaphone fs-1 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Events</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>User</th>
                                                <th>Start</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $recent_events = $conn->query("
                                                    SELECT e.*, u.username 
                                                    FROM events e
                                                    JOIN users u ON e.user_id = u.id
                                                    ORDER BY e.created_at DESC LIMIT 5
                                                ");

                                            while ($event = $recent_events->fetch()):
                                                $status_class = match ($event['status']) {
                                                    'pending' => 'warning',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($event['title']) ?></td>
                                                    <td><?= htmlspecialchars($event['username']) ?></td>
                                                    <td><?= date('M d, Y H:i', strtotime($event['start'])) ?></td>
                                                    <td><span
                                                            class="badge bg-<?= $status_class ?> status-badge"><?= ucfirst($event['status']) ?></span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Recent User Activity</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Action</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $recent_logs = $conn->query("
                                                    SELECT l.*, u.username 
                                                    FROM audit_logs l
                                                    JOIN users u ON l.user_id = u.id
                                                    ORDER BY l.created_at DESC LIMIT 5
                                                ");

                                            if ($recent_logs->rowCount() > 0):
                                                while ($log = $recent_logs->fetch()):
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($log['username']) ?></td>
                                                        <td><?= htmlspecialchars($log['action']) ?></td>
                                                        <td><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></td>
                                                    </tr>
                                                    <?php
                                                endwhile;
                                            else:
                                                ?>
                                                <tr>
                                                    <td colspan="3">No recent activity</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Management Tab -->
            <div id="users" class="tab-content">
                <div class="page-header">
                    <h1 class="h2"><i class="bi bi-people me-2"></i> Users Management</h1>
                    <p class="lead">Manage system users and their permissions</p>
                </div>

                <?= $role_message ?? '' ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">All Users</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Registered</th>
                                        <th>Role</th>
                                        <th>Events</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $users_query = $conn->query("
                            SELECT u.*, 
                                (SELECT COUNT(*) FROM events WHERE user_id = u.id) as event_count
                            FROM users u
                            ORDER BY u.created_at DESC
                        ");

                                    while ($user = $users_query->fetch()):
                                        ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                                    </div>
                                                    <?= htmlspecialchars($user['username']) ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <span
                                                    class="badge <?= $user['role'] === 'admin' ? 'bg-primary' : 'bg-secondary' ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td><?= $user['event_count'] ?></td>
                                            <td>
                                                <div class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <div class="input-group">
                                                        <select name="role" class="form-select form-select-sm role-select"
                                                            data-user-id="<?= $user['id'] ?>">
                                                            <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>User</option>
                                                            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                                        </select>
                                                        <button type="button" class="btn btn-sm btn-primary update-role-btn"
                                                            data-user-id="<?= $user['id'] ?>">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <button class="btn btn-sm btn-info view-user-events ms-1"
                                                    data-user-id="<?= $user['id'] ?>"
                                                    data-username="<?= htmlspecialchars($user['username']) ?>"
                                                    data-bs-toggle="modal" data-bs-target="#userEventsModal">
                                                    <i class="bi bi-calendar-event"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal for viewing user events -->
            <div class="modal fade" id="userEventsModal" tabindex="-1" aria-labelledby="userEventsModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="userEventsModalLabel">Events for <span
                                    id="modal-username"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="filter-container mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="user-event-status-filter" class="form-label">Status</label>
                                        <select id="user-event-status-filter" class="form-select">
                                            <option value="all">All Statuses</option>
                                            <option value="pending">Pending</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="user-event-date-filter" class="form-label">Date Range</label>
                                        <select id="user-event-date-filter" class="form-select">
                                            <option value="all">All Dates</option>
                                            <option value="today">Today</option>
                                            <option value="this_week">This Week</option>
                                            <option value="this_month">This Month</option>
                                            <option value="future">Future</option>
                                            <option value="past">Past</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="user-event-search" class="form-label">Search</label>
                                        <input type="text" id="user-event-search" class="form-control"
                                            placeholder="Search events...">
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover" id="userEventsTable">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Start</th>
                                            <th>End</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="user-events-content">
                                        <!-- Events will be loaded here via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events Tab -->
            <div id="events" class="tab-content">
                <div class="page-header">
                    <h1 class="h2"><i class="bi bi-calendar-event me-2"></i>All Events</h1>
                    <p class="lead">View and manage all system events</p>
                </div>

                <div class="filter-container">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="event-status-filter" class="form-label">Status</label>
                            <select id="event-status-filter" class="form-select">
                                <option value="all">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="event-date-filter" class="form-label">Date Range</label>
                            <select id="event-date-filter" class="form-select">
                                <option value="all">All Dates</option>
                                <option value="today">Today</option>
                                <option value="this_week">This Week</option>
                                <option value="this_month">This Month</option>
                                <option value="future">Future</option>
                                <option value="past">Past</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="event-user-filter" class="form-label">User</label>
                            <select id="event-user-filter" class="form-select">
                                <option value="all">All Users</option>
                                <?php
                                $users = $conn->query("SELECT id, username FROM users ORDER BY username");
                                while ($user = $users->fetch()):
                                    ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="event-search" class="form-label">Search</label>
                            <input type="text" id="event-search" class="form-control" placeholder="Search events...">
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="eventsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>User</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Repeat</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $events_query = $conn->query("
                                SELECT e.*, u.username 
                                FROM events e
                                JOIN users u ON e.user_id = u.id
                                ORDER BY e.start DESC
                                LIMIT 100
                            ");

                                    while ($event = $events_query->fetch()):
                                        $status_class = match ($event['status']) {
                                            'pending' => 'warning',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <tr data-status="<?= $event['status'] ?>" data-user-id="<?= $event['user_id'] ?>">
                                            <td><?= $event['id'] ?></td>
                                            <td><?= htmlspecialchars($event['title']) ?></td>
                                            <td><?= htmlspecialchars($event['username']) ?></td>
                                            <td><?= date('M d, Y H:i', strtotime($event['start'])) ?></td>
                                            <td><?= date('M d, Y H:i', strtotime($event['end'])) ?></td>
                                            <td><?= $event['repeat_type'] ? ucfirst($event['repeat_type']) : 'None' ?>
                                            </td>
                                            <td><span
                                                    class="badge bg-<?= $status_class ?> status-badge"><?= ucfirst($event['status']) ?></span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info view-event"
                                                    data-id="<?= $event['id'] ?>">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Event Details Modal -->
            <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Event Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="event-details">
                            <!-- Event details will be loaded here via AJAX -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Announcements Tab -->
            <div id="announcements" class="tab-content">
                <div class="page-header">
                    <h1 class="h2"><i class="bi bi-megaphone me-2"></i>Announcements</h1>
                    <p class="lead">Create and manage system announcements</p>
                </div>

                <?= $announcement_message ?? '' ?>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Create New Announcement</h6>
                            </div>
                            <div class="card-body">
                                <form method="post" id="announcementForm">
                                    <div class="mb-3">
                                        <label for="announcement_title" class="form-label">Title</label>
                                        <input type="text" class="form-control" id="announcement_title"
                                            name="announcement_title" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="announcement_message" class="form-label">Message</label>
                                        <textarea class="form-control" id="announcement_message"
                                            name="announcement_message" rows="4" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="expires_at" class="form-label">Expires At</label>
                                        <input type="datetime-local" class="form-control" id="expires_at"
                                            name="expires_at" required>
                                    </div>
                                    <button type="submit" name="create_announcement" class="btn btn-primary">
                                        <i class="bi bi-send me-1"></i> Create Announcement
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
    <div class="card shadow">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Active Announcements</h6>
        </div>
        <div class="card-body">
            <div class="list-group">
                <?php
                $announcements_query = $conn->query("
                    SELECT a.*, u.username 
                    FROM announcements a
                    JOIN users u ON a.created_by = u.id
                    WHERE a.expires_at > NOW()
                    ORDER BY a.created_at DESC
                ");

                if ($announcements_query->rowCount() > 0):
                    while ($announcement = $announcements_query->fetch()):
                        ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?= htmlspecialchars($announcement['title']) ?></h5>
                                <small><?= date('M d, Y H:i', strtotime($announcement['created_at'])) ?></small>
                            </div>
                            <p class="mb-1"><?= htmlspecialchars($announcement['message']) ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    By: <?= htmlspecialchars($announcement['username']) ?> |
                                    Expires: <?= date('M d, Y H:i', strtotime($announcement['expires_at'])) ?>
                                </small>
                                <!-- PUT THE DELETE FORM RIGHT HERE -->
                                <form method="post" data-announcement-delete>
                                    <input type="hidden" name="announcement_id" value="<?= $announcement['id'] ?>">
                                    <button class="btn btn-sm btn-danger delete-announcement-btn" data-announcement-id="<?= $announcement['id'] ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                                </form>
                            </div>
                        </div>
                        <?php
                    endwhile;
                else:
                    ?>
                    <div class="list-group-item text-center py-4">
                        <i class="bi bi-megaphone fs-1 text-muted mb-2"></i>
                        <p class="mb-0">No active announcements</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Logs Tab -->
            <div id="audit" class="tab-content">
                <div class="page-header">
                    <h1 class="h2"><i class="bi bi-list-check me-2"></i>Audit Logs</h1>
                    <p class="lead">System activity and user actions</p>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuLink">
                                <li><a class="dropdown-item" href="#" id="exportLogs">Export to CSV</a></li>
                                <li><a class="dropdown-item" href="#" id="clearLogs">Clear Logs</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="filter-container mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="audit-user-filter" class="form-label">User</label>
                                    <select id="audit-user-filter" class="form-select">
                                        <option value="all">All Users</option>
                                        <?php
                                        $users = $conn->query("SELECT id, username FROM users ORDER BY username");
                                        while ($user = $users->fetch()):
                                            ?>
                                            <option value="<?= $user['id'] ?>">
                                                <?= htmlspecialchars($user['username']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="audit-date-filter" class="form-label">Date Range</label>
                                    <select id="audit-date-filter" class="form-select">
                                        <option value="all">All Dates</option>
                                        <option value="today">Today</option>
                                        <option value="this_week">This Week</option>
                                        <option value="this_month">This Month</option>
                                        <option value="last_7_days">Last 7 Days</option>
                                        <option value="last_30_days">Last 30 Days</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="audit-search" class="form-label">Search</label>
                                    <input type="text" id="audit-search" class="form-control"
                                        placeholder="Search logs...">
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="auditTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $logs_query = $conn->query("
                                            SELECT l.*, u.username 
                                            FROM audit_logs l
                                            JOIN users u ON l.user_id = u.id
                                            ORDER BY l.created_at DESC
                                            LIMIT 100
                                        ");

                                    if ($logs_query->rowCount() > 0):
                                        while ($log = $logs_query->fetch()):
                                            ?>
                                            <tr data-user-id="<?= $log['user_id'] ?>"
                                                data-timestamp="<?= strtotime($log['created_at']) ?>">
                                                <td><?= $log['id'] ?></td>
                                                <td><?= htmlspecialchars($log['username']) ?></td>
                                                <td><?= htmlspecialchars($log['action']) ?></td>
                                                <td><?= date('M d, Y H:i:s', strtotime($log['created_at'])) ?></td>
                                            </tr>
                                            <?php
                                        endwhile;
                                    else:
                                        ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">
                                                <i class="bi bi-info-circle fs-1 text-muted mb-2"></i>
                                                <p class="mb-0">No audit logs found</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    
    $(document).ready(function () {
        // Initialize DataTables with better pagination handling
        $('#usersTable, #eventsTable, #auditTable').DataTable({
    responsive: true,
    pageLength: 25,
    lengthMenu: [25, 50, 100],
    searching: false, // Disable the search box
    initComplete: function () {
        $('.dataTables_length select').css({
            'width': 'auto',
            'min-width': '150px'
        });
    },
    // Add these settings to prevent empty pages
    drawCallback: function (settings) {
        var api = this.api();
        var recordsTotal = api.page.info().recordsTotal;
        var recordsDisplay = api.page.info().recordsDisplay;
        var page = api.page.info().page;
        var pages = api.page.info().pages;

        // If no records after filtering, show first page
        if (recordsDisplay === 0) {
            api.page('first').draw('page');
        }
        // If current page is empty but there are records, go to previous page
        else if (api.rows({ page: 'current' }).data().length === 0 && page > 0) {
            api.page(page - 1).draw('page');
        }
    }
});


        // Tab Navigation
        $('.nav-link').click(function (e) {
            const tab = $(this).data('tab');

            // Only trigger tab switch if the link has a data-tab attribute
            if (!tab) return;

            e.preventDefault();
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
            $('.tab-content').removeClass('active');
            $('#' + tab).addClass('active');
        });

        $(document).ready(function () {
            // ========== FIX 1: ROLE UPDATE FUNCTIONALITY ==========
            $('.update-role-btn').click(function () {
                const userId = $(this).data('user-id');
                const selectedRole = $(this).closest('td').find('.role-select').val();
                const roleSpan = $(this).closest('tr').find('td:eq(4) span');

                // Show loading indicator
                $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                $(this).prop('disabled', true);

                $.ajax({
                    url: 'admin_index.php',
                    method: 'POST',
                    data: {
                        update_role: 1,
                        user_id: userId,
                        role: selectedRole
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            // Update the role label in the UI
                            roleSpan.removeClass('bg-primary bg-secondary')
                                .addClass(selectedRole === 'admin' ? 'bg-primary' : 'bg-secondary')
                                .text(selectedRole.charAt(0).toUpperCase() + selectedRole.slice(1));

                            // Show success message that auto-dismisses
                            const alertMessage = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                                'User role updated successfully!' +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                '</div>');

                            $('#users .page-header').after(alertMessage);
                            setTimeout(function () {
                                alertMessage.alert('close');
                            }, 3000);
                        } else {
                            // Show error message
                            const alertMessage = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                                'Failed to update user role: ' + (response.message || 'Unknown error') +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                '</div>');

                            $('#users .page-header').after(alertMessage);
                        }
                    },
                    error: function (xhr) {
                        // Show detailed error message
                        const alertMessage = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                            'Server error: ' + (xhr.status + ' ' + xhr.statusText) +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                            '</div>');

                        $('#users .page-header').after(alertMessage);
                    },
                    complete: function () {
                        // Restore button state regardless of outcome
                        $('.update-role-btn[data-user-id="' + userId + '"]').html('<i class="bi bi-check-lg"></i>');
                        $('.update-role-btn[data-user-id="' + userId + '"]').prop('disabled', false);
                    }
                });
            });

            // ========== FIX 2: VIEW USER EVENTS FUNCTIONALITY ==========
            // View User Events Button
            $('.view-user-events').click(function () {
                const userId = $(this).data('user-id');
                const username = $(this).data('username');

                $('#modal-username').text(username);
                $('#user-events-content').html(`
        <tr>
            <td colspan="4" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </td>
        </tr>
    `);

                const modalEl = document.getElementById('userEventsModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();

                $.ajax({
                    url: 'admin/get_user_events.php',
                    method: 'GET',
                    data: { user_id: userId },
                    dataType: 'json',
                    success: function (response) {
                        if (response.error) {
                            $('#user-events-content').html(`<tr><td colspan="4" class="text-center py-4 text-danger">Error: ${response.error}</td></tr>`);
                            return;
                        }

                        const events = response.events || [];
                        if (events.length === 0) {
                            $('#user-events-content').html('<tr><td colspan="4" class="text-center py-4">No events found for this user</td></tr>');
                            return;
                        }

                        let html = events.map(event => `
                <tr>
                    <td>${event.title}</td>
                    <td>${formatDateTime(event.start)}</td>
                    <td>${event.end ? formatDateTime(event.end) : 'N/A'}</td>
                    <td><span class="badge ${getStatusBadgeClass(event.status)}">${capitalizeFirstLetter(event.status)}</span></td>
                </tr>
            `).join('');

                        $('#user-events-content').html(html);

                        // Avoid adding a second "Close" button — let footer handle it
                        initUserEventsFiltering();
                    },
                    error: function (xhr, status, error) {
                        $('#user-events-content').html(`<tr><td colspan="4" class="text-center py-4 text-danger">Error loading events: ${status} ${error}</td></tr>`);
                    }
                });
            });



            // ========== FIX 3: USER EVENTS FILTERING ==========
            function initUserEventsFiltering() {
                $('#user-event-status-filter, #user-event-date-filter, #user-event-search').off('change keyup').on('change keyup', function () {
                    const status = $('#user-event-status-filter').val();
                    const dateRange = $('#user-event-date-filter').val();
                    const searchTerm = $('#user-event-search').val().toLowerCase();
                    const now = new Date();

                    $('#userEventsTable tbody tr').each(function () {
                        const row = $(this);

                        // Skip error/empty messages
                        if (row.find('td[colspan]').length > 0) {
                            return;
                        }

                        const rowStatus = row.find('td:eq(3)').text().trim().toLowerCase();

                        // Handle date parsing safely
                        let startDate;
                        try {
                            startDate = new Date(row.find('td:eq(1)').text());
                            if (isNaN(startDate.getTime())) {
                                startDate = now; // Default to current date if parsing fails
                            }
                        } catch (e) {
                            startDate = now; // Default to current date if parsing fails
                        }

                        const title = row.find('td:eq(0)').text().toLowerCase();

                        // Status filter
                        const statusMatch = status === 'all' || rowStatus.includes(status.toLowerCase());

                        // Date range filter
                        let dateMatch = true;
                        if (dateRange !== 'all') {
                            if (dateRange === 'today') {
                                dateMatch = startDate.toDateString() === now.toDateString();
                            } else if (dateRange === 'this_week') {
                                const weekStart = new Date(now);
                                weekStart.setDate(now.getDate() - now.getDay());
                                weekStart.setHours(0, 0, 0, 0);
                                dateMatch = startDate >= weekStart;
                            } else if (dateRange === 'this_month') {
                                dateMatch = startDate.getMonth() === now.getMonth() &&
                                    startDate.getFullYear() === now.getFullYear();
                            } else if (dateRange === 'future') {
                                dateMatch = startDate > now;
                            } else if (dateRange === 'past') {
                                dateMatch = startDate < now;
                            }
                        }

                        // Search filter
                        const searchMatch = searchTerm === '' || title.includes(searchTerm);

                        // Show/hide based on all filters
                        if (statusMatch && dateMatch && searchMatch) {
                            row.show();
                        } else {
                            row.hide();
                        }
                    });

                    // Check if any visible rows exist
                    const visibleRows = $('#userEventsTable tbody tr:visible').length;
                    if (visibleRows === 0) {
                        // No visible rows after filtering - show a message
                        if ($('#no-matching-events').length === 0) {
                            $('#userEventsTable tbody').append('<tr id="no-matching-events"><td colspan="4" class="text-center py-4">No events match your filters</td></tr>');
                        }
                    } else {
                        // Remove the "no matching events" message if it exists
                        $('#no-matching-events').remove();
                    }
                });
            }

            
            // ========== HELPER FUNCTIONS ==========
            function getStatusBadgeClass(status) {
                switch (status.toLowerCase()) {
                    case 'pending': return 'bg-warning';
                    case 'completed': return 'bg-success';
                    case 'cancelled': return 'bg-danger';
                    default: return 'bg-secondary';
                }
            }

            function capitalizeFirstLetter(string) {
                if (!string) return '';
                return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
            }

            function formatDateTime(dateTimeString) {
                if (!dateTimeString) return 'N/A';

                try {
                    const date = new Date(dateTimeString);
                    if (isNaN(date.getTime())) {
                        return dateTimeString; // Return original if parsing fails
                    }

                    return date.toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } catch (e) {
                    return dateTimeString; // Return original if formatting fails
                }
            }
        });




        // ========== EVENT DETAILS MODAL ==========

        $(document).ready(function () {
            $(document).on('click', '.view-event', function () {
                var eventId = $(this).data('id');
                var modal = $('#eventModal');

                // Show loading state
                $('#event-details').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);

                modal.modal('show');

                $.ajax({
                    url: 'admin/get_event_details.php',
                    type: 'GET',
                    data: { id: eventId },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success && response.event) {
                            // Use the new response format with event object
                            const event = response.event;
                            const startDate = event.start ? new Date(event.start) : null;
                            const endDate = event.end ? new Date(event.end) : null;

                            const eventDetails = `
                        <div class="event-details-content">
                            <h4>${escapeHtml(event.title)}</h4>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Start:</strong> ${startDate ? formatDateTime(startDate) : 'Not specified'}</p>
                                    <p><strong>End:</strong> ${endDate ? formatDateTime(endDate) : 'Not specified'}</p>
                                    <p><strong>Status:</strong> <span class="badge ${getStatusBadgeClass(event.status)}">${capitalizeFirstLetter(event.status)}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Repeat Type:</strong> ${event.repeat_type ? capitalizeFirstLetter(event.repeat_type) : 'None'}</p>
                                    <p><strong>Created by:</strong> ${escapeHtml(event.username)}</p>
                                    <p><strong>Description:</strong> ${event.description ? escapeHtml(event.description) : 'None'}</p>
                                </div>
                            </div>
                        </div>
                    `;

                            $('#event-details').html(eventDetails);
                        } else {
                            $('#event-details').html(`
                        <div class="alert alert-danger">
                            ${response.error || 'Unknown error occurred'}
                            ${response.debug ? '<br><small>' + response.debug + '</small>' : ''}
                        </div>
                    `);
                        }
                    },
                    error: function (xhr) {
                        let errorMessage = 'Request failed';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        } else if (xhr.responseText) {
                            errorMessage = 'Invalid response: ' + xhr.responseText.substring(0, 100);
                        }

                        $('#event-details').html(`
                    <div class="alert alert-danger">
                        ${errorMessage}
                        <button class="btn btn-sm btn-light mt-2" 
                                onclick="console.log('Full response:', ${JSON.stringify(xhr.responseText)})">
                            Show Technical Details
                        </button>
                    </div>
                `);
                    }
                });
            });


            // ========== HELPER FUNCTIONS ==========
            function formatDateTime(date) {
                return date.toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            function getStatusBadgeClass(status) {
                switch (status.toLowerCase()) {
                    case 'pending': return 'bg-warning';
                    case 'completed': return 'bg-success';
                    case 'cancelled': return 'bg-danger';
                    default: return 'bg-secondary';
                }
            }

            function capitalizeFirstLetter(string) {
                return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
            }

            function escapeHtml(unsafe) {
                if (!unsafe) return '';
                return unsafe.toString()
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            function showAlert(type, message) {
                const alert = $(`
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);

                $('#events .page-header').after(alert);

                setTimeout(() => {
                    alert.alert('close');
                }, 5000);
            }
        });




        // ==== EVENTS FILTERS =====
        document.getElementById('event-status-filter').addEventListener('change', filterEvents);
        document.getElementById('event-date-filter').addEventListener('change', filterEvents);
        document.getElementById('event-user-filter').addEventListener('change', filterEvents);
        document.getElementById('event-search').addEventListener('input', filterEvents);

        function filterEvents() {
            let status = document.getElementById('event-status-filter').value;
            let date = document.getElementById('event-date-filter').value;
            let user = document.getElementById('event-user-filter').value;
            let searchQuery = document.getElementById('event-search').value.toLowerCase();

            let rows = document.querySelectorAll('#eventsTable tbody tr');

            rows.forEach(row => {
                let rowStatus = row.getAttribute('data-status');
                let rowUserId = row.getAttribute('data-user-id');
                let rowTitle = row.querySelector('td:nth-child(2)').innerText.toLowerCase();
                let rowDate = new Date(row.querySelector('td:nth-child(4)').innerText); // Assuming the date is in the 4th column
                let showRow = true;

                // Filter by status
                if (status !== 'all' && rowStatus !== status) {
                    showRow = false;
                }

                // Filter by user
                if (user !== 'all' && rowUserId !== user) {
                    showRow = false;
                }

                // Filter by search query
                if (searchQuery && !rowTitle.includes(searchQuery)) {
                    showRow = false;
                }

                // Filter by date range
                const now = new Date();
                switch (date) {
                    case 'today':
                        if (!isSameDay(rowDate, now)) {
                            showRow = false;
                        }
                        break;
                    case 'this_week':
                        if (!isSameWeek(rowDate, now)) {
                            showRow = false;
                        }
                        break;
                    case 'this_month':
                        if (rowDate.getMonth() !== now.getMonth() || rowDate.getFullYear() !== now.getFullYear()) {
                            showRow = false;
                        }
                        break;
                    case 'future':
                        if (rowDate < now) {
                            showRow = false;
                        }
                        break;
                    case 'past':
                        if (rowDate > now) {
                            showRow = false;
                        }
                        break;
                }

                // Show or hide row
                if (showRow) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Helper function to check if two dates are the same day
        function isSameDay(date1, date2) {
            return date1.getDate() === date2.getDate() &&
                date1.getMonth() === date2.getMonth() &&
                date1.getFullYear() === date2.getFullYear();
        }

        // Helper function to check if two dates are in the same week
        function isSameWeek(date1, date2) {
            const startOfWeek = new Date(date2);
            startOfWeek.setDate(date2.getDate() - date2.getDay()); // Set to Monday of the week
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6); // End of the week (Sunday)

            return date1 >= startOfWeek && date1 <= endOfWeek;
        }

        // Announcement
        // In your JavaScript, modify the announcement form submission handler
$('#announcementForm').on('submit', function(e) {
    e.preventDefault(); // Prevent the default form submission
    
    // Get form data
    const formData = $(this).serialize();
    
    // Show loading state
    const submitBtn = $(this).find('[type="submit"]');
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...');
    
    $.ajax({
        url: 'admin_index.php',
        method: 'POST',
        data: formData + '&create_announcement=1',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Add the new announcement to the list without reloading
                addAnnouncementToUI(response.announcement);
                
                // Show success message
                showAlert('success', 'Announcement created successfully!');
                
                // Reset the form
                $('#announcementForm')[0].reset();
            } else {
                showAlert('danger', response.message || 'Failed to create announcement');
            }
        },
        error: function(xhr) {
            showAlert('danger', 'Error: ' + xhr.statusText);
        },
        complete: function() {
            submitBtn.prop('disabled', false).html('<i class="bi bi-send me-1"></i> Create Announcement');
        }
    });
});

function addAnnouncementToUI(announcement) {
    const announcementHtml = `
        <div class="list-group-item list-group-item-action">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">${escapeHtml(announcement.title)}</h5>
                <small>Just now</small>
            </div>
            <p class="mb-1">${escapeHtml(announcement.message)}</p>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    By: <?= htmlspecialchars($_SESSION['username']) ?> |
                    Expires: ${formatDateTime(announcement.expires_at)}
                </small>
                <form method="post" class="ms-2">
                    <input type="hidden" name="announcement_id" value="${announcement.id}">
                    <button type="submit" name="delete_announcement" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    `;
    
    // Prepend to the announcements list
    $('.list-group').prepend(announcementHtml);
    
    // If there was a "no announcements" message, remove it
    $('.list-group-item.text-center').remove();
}

// Handle announcement deletion via AJAX
$(document).on('submit', 'form[data-announcement-delete]', function(e) {
    e.preventDefault(); // Prevent default form submission
    
    const form = $(this);
    const announcementId = form.find('input[name="announcement_id"]').val();
    const announcementItem = form.closest('.list-group-item');
    
    // Show loading state
    const deleteBtn = form.find('button[name="delete_announcement"]');
    deleteBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span>');
    
    $.ajax({
        url: window.location.href, // Submit to the current page
        method: 'POST',
        data: {
            delete_announcement: 1,
            announcement_id: announcementId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Remove the announcement from UI
                announcementItem.fadeOut(300, function() {
                    $(this).remove();
                    
                    // If no announcements left, show empty message
                    if ($('.list-group-item').length === 0) {
                        $('.list-group').html(`
                            <div class="list-group-item text-center py-4">
                                <i class="bi bi-megaphone fs-1 text-muted mb-2"></i>
                                <p class="mb-0">No active announcements</p>
                            </div>
                        `);
                    }
                });
                
                // Show success message
                showAlert('success', 'Announcement deleted successfully!');
            } else {
                showAlert('danger', response.message || 'Failed to delete announcement');
                deleteBtn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
            }
        },
        error: function(xhr) {
            showAlert('danger', 'Error: ' + xhr.statusText);
            deleteBtn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
        }
    });
});

function showAlert(type, message) {
    const alert = $(`
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `);
    
    $('#announcements .page-header').after(alert);
    
    setTimeout(() => {
        alert.alert('close');
    }, 5000);
}

function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}



        // Audit Logs Filtering
        $('#audit-user-filter, #audit-date-filter, #audit-search').on('change keyup', function () {
            const userId = $('#audit-user-filter').val();
            const dateRange = $('#audit-date-filter').val();
            const searchTerm = $('#audit-search').val().toLowerCase();
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const weekAgo = new Date(today);
            weekAgo.setDate(weekAgo.getDate() - 7);
            const monthAgo = new Date(today);
            monthAgo.setMonth(monthAgo.getMonth() - 1);
            const thirtyDaysAgo = new Date(today);
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

            $('#auditTable tbody tr').each(function () {
                const row = $(this);
                const rowUserId = row.data('user-id');
                const rowTimestamp = row.data('timestamp') * 1000; // Convert to milliseconds
                const rowDate = new Date(rowTimestamp);
                const action = row.find('td:eq(2)').text().toLowerCase();
                const user = row.find('td:eq(1)').text().toLowerCase();

                // User filter
                const userMatch = userId === 'all' || rowUserId == userId;

                // Date range filter
                let dateMatch = true;
                if (dateRange !== 'all') {
                    if (dateRange === 'today') {
                        dateMatch = rowDate >= today;
                    } else if (dateRange === 'this_week') {
                        const weekStart = new Date(now.setDate(now.getDate() - now.getDay()));
                        dateMatch = rowDate >= weekStart;
                    } else if (dateRange === 'this_month') {
                        dateMatch = rowDate.getMonth() === now.getMonth() && rowDate.getFullYear() === now.getFullYear();
                    } else if (dateRange === 'last_7_days') {
                        dateMatch = rowDate >= weekAgo;
                    } else if (dateRange === 'last_30_days') {
                        dateMatch = rowDate >= thirtyDaysAgo;
                    }
                }

                // Search filter
                const searchMatch = searchTerm === '' ||
                    action.includes(searchTerm) ||
                    user.includes(searchTerm) ||
                    row.find('td:eq(0)').text().includes(searchTerm);

                // Show/hide based on all filters
                if (userMatch && dateMatch && searchMatch) {
                    row.show();
                } else {
                    row.hide();
                }
            });
        });

        // Helper functions
        function getStatusBadgeClass(status) {
            switch (status) {
                case 'pending': return 'bg-warning';
                case 'completed': return 'bg-success';
                case 'cancelled': return 'bg-danger';
                default: return 'bg-secondary';
            }
        }

        function capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function formatDateTime(dateTimeString) {
            const date = new Date(dateTimeString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Export Logs to CSV
        $('#exportLogs').on('click', function (e) {
            e.preventDefault();

            const csvData = [];
            const headers = [];

            // Collect table headers
            $('#auditTable thead th').each(function () {
                headers.push($(this).text().trim());
            });
            csvData.push(headers.join(','));

            // Collect visible table rows
            $('#auditTable tbody tr:visible').each(function () {
                const row = [];
                $(this).find('td').each(function () {
                    const cellText = $(this).text().trim().replace(/"/g, '""'); // Escape quotes
                    row.push(`"${cellText}"`);
                });
                csvData.push(row.join(','));
            });

            // Generate CSV and trigger download
            const csvContent = csvData.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);

            const downloadLink = document.createElement('a');
            downloadLink.href = url;
            downloadLink.download = 'audit_logs.csv';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            URL.revokeObjectURL(url); // Clean up URL object
        });

        // Clear Logs from Table
        $('#clearLogs').on('click', function (e) {
            e.preventDefault();

            const confirmClear = confirm('Are you sure you want to clear all audit logs? This action cannot be undone.');
            if (confirmClear) {
                $('#auditTable tbody').empty();
            }
        });

    });


</script>

</body>

</html>