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

// Check if user has admin role directly from session
// This is more efficient than querying the database again
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Not an admin, redirect to regular user page
    header("Location: index.php");
    exit();
}
if ($conn instanceof mysqli) {
    // Get connection parameters
    $servername = "localhost"; // Use the same as in db.php
    $username = "root"; // Use the same as in db.php
    $password = ""; // Use the same as in db.php
    $dbname = "calendar_system"; // Use the same as in db.php
    
    // Close the mysqli connection
    mysqli_close($conn);
    
    // Create a new PDO connection
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        // Set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
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
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #f8f9fa;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .nav-link {
            font-weight: 500;
            color: #333;
        }
        .nav-link.active {
            color: #2470dc;
        }
        main {
            padding-top: 48px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark fixed-top px-3">
        <a class="navbar-brand" href="#">Calendar System Admin</a>
        <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap">
                <a class="nav-link" href="../logout.php">Sign out</a>
            </li>
        </ul>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
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
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Dashboard</h1>
                    </div>
                    
                    <div class="row">
                        <?php
                        // Get counts for dashboard
                        $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
                        $total_events = $conn->query("SELECT COUNT(*) as count FROM events")->fetch()['count'];
                        $active_events = $conn->query("SELECT COUNT(*) as count FROM events WHERE status != 'cancelled'")->fetch()['count'];
                        $total_announcements = $conn->query("SELECT COUNT(*) as count FROM announcements")->fetch()['count'];
                        ?>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Users</h5>
                                    <h2><?= $total_users ?></h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Active Events</h5>
                                    <h2><?= $active_events ?></h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <h5 class="card-title">Total Events</h5>
                                    <h2><?= $total_events ?></h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Announcements</h5>
                                    <h2><?= $total_announcements ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h3>Recent Events</h3>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
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
                                            $status_class = match($event['status']) {
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
                                                <td><span class="badge bg-<?= $status_class ?>"><?= $event['status'] ?></span></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h3>Recent User Activity</h3>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
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
                                            <tr><td colspan="3">No recent activity</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Management Tab -->
                <div id="users" class="tab-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Users Management</h1>
                    </div>
                    
                    <?= $role_message ?? '' ?>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
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
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                        <td><?= $user['role'] ?></td>
                                        <td><?= $user['event_count'] ?></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <select name="role" class="form-select form-select-sm d-inline-block w-auto">
                                                    <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>User</option>
                                                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                                </select>
                                                <button type="submit" name="update_role" class="btn btn-sm btn-primary">Update</button>
                                            </form>
                                            <button class="btn btn-sm btn-info view-user-events" data-user-id="<?= $user['id'] ?>">View Events</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Events Tab -->
                <div id="events" class="tab-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">All Events</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <select id="event-filter" class="form-select">
                                    <option value="all">All Events</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped" id="events-table">
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
                                ");
                                
                                while ($event = $events_query->fetch()):
                                    $status_class = match($event['status']) {
                                        'pending' => 'warning',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>
                                    <tr class="event-row" data-status="<?= $event['status'] ?>">
                                        <td><?= $event['id'] ?></td>
                                        <td><?= htmlspecialchars($event['title']) ?></td>
                                        <td><?= htmlspecialchars($event['username']) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($event['start'])) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($event['end'])) ?></td>
                                        <td><?= $event['repeat_type'] ?></td>
                                        <td><span class="badge bg-<?= $status_class ?>"><?= $event['status'] ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-info view-event" data-id="<?= $event['id'] ?>">View</button>
                                            <button class="btn btn-sm btn-warning change-status" data-id="<?= $event['id'] ?>" data-status="<?= $event['status'] ?>">Change Status</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Event Details Modal -->
                    <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Event Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body" id="event-details">
                                    <!-- Event details will be loaded here -->
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Change Status Modal -->
                    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Change Event Status</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="change-status-form">
                                        <input type="hidden" id="status-event-id">
                                        <div class="mb-3">
                                            <label for="event-status" class="form-label">Status</label>
                                            <select id="event-status" class="form-select">
                                                <option value="pending">Pending</option>
                                                <option value="completed">Completed</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="save-status">Save Changes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Announcements Tab -->
                <div id="announcements" class="tab-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Announcements</h1>
                    </div>
                    
                    <?= $announcement_message ?? '' ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Create New Announcement</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="announcement_title" class="form-label">Title</label>
                                            <input type="text" class="form-control" id="announcement_title" name="announcement_title" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="announcement_message" class="form-label">Message</label>
                                            <textarea class="form-control" id="announcement_message" name="announcement_message" rows="4" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="expires_at" class="form-label">Expires At</label>
                                            <input type="datetime-local" class="form-control" id="expires_at" name="expires_at" required>
                                        </div>
                                        <button type="submit" name="create_announcement" class="btn btn-primary">Create Announcement</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h3>Current Announcements</h3>
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
                                        <small>By: <?= htmlspecialchars($announcement['username']) ?> | Expires: <?= date('M d, Y H:i', strtotime($announcement['expires_at'])) ?></small>
                                        <form method="post" class="mt-2">
                                            <input type="hidden" name="announcement_id" value="<?= $announcement['id'] ?>">
                                            <button type="submit" name="delete_announcement" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <div class="list-group-item">No active announcements</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Audit Logs Tab -->
                <div id="audit" class="tab-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Audit Logs</h1>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
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
                                    <tr>
                                        <td><?= $log['id'] ?></td>
                                        <td><?= htmlspecialchars($log['username']) ?></td>
                                        <td><?= htmlspecialchars($log['action']) ?></td>
                                        <td><?= date('M d, Y H:i:s', strtotime($log['created_at'])) ?></td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <tr><td colspan="4">No audit logs found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab Navigation
        const navLinks = document.querySelectorAll('.nav-link');
        const tabContents = document.querySelectorAll('.tab-content');
        
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links and tabs
                navLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked link and corresponding tab
                this.classList.add('active');
                const tab = this.getAttribute('data-tab');
                document.getElementById(tab).classList.add('active');
            });
        });
        
        // Event filter
        const eventFilter = document.getElementById('event-filter');
        if (eventFilter) {
            eventFilter.addEventListener('change', function() {
                const filter = this.value;
                const rows = document.querySelectorAll('#events-table tbody tr');
                
                rows.forEach(row => {
                    if (filter === 'all' || row.getAttribute('data-status') === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // View Event Details
        const viewEventButtons = document.querySelectorAll('.view-event');
        viewEventButtons.forEach(button => {
            button.addEventListener('click', function() {
                const eventId = this.getAttribute('data-id');
                
                fetch('get_event_details.php?id=' + eventId)
                    .then(response => response.json())
                    .then(data => {
                        let html = `
                            <h4>${data.title}</h4>
                            <p>${data.description || 'No description'}</p>
                            <p><strong>Start:</strong> ${new Date(data.start).toLocaleString()}</p>
                            <p><strong>End:</strong> ${new Date(data.end).toLocaleString()}</p>
                            <p><strong>Location:</strong> ${data.location || 'No location'}</p>
                            <p><strong>Status:</strong> ${data.status}</p>
                            <p><strong>Repeat Type:</strong> ${data.repeat_type}</p>
                            <p><strong>Created By:</strong> ${data.username}</p>
                        `;
                        
                        document.getElementById('event-details').innerHTML = html;
                        new bootstrap.Modal(document.getElementById('eventModal')).show();
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
        
        // Change Event Status
        const changeStatusButtons = document.querySelectorAll('.change-status');
        changeStatusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const eventId = this.getAttribute('data-id');
                const currentStatus = this.getAttribute('data-status');
                
                document.getElementById('status-event-id').value = eventId;
                document.getElementById('event-status').value = currentStatus;
                
                new bootstrap.Modal(document.getElementById('statusModal')).show();
            });
        });
        
        // Save Status Change
        document.getElementById('save-status').addEventListener('click', function() {
            const eventId = document.getElementById('status-event-id').value;
            const newStatus = document.getElementById('event-status').value;
            
            fetch('update_event_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${eventId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating status');
            });
        });

        // View User Events
        const viewUserEventsButtons = document.querySelectorAll('.view-user-events');
        viewUserEventsButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                
                fetch('get_user_events.php?user_id=' + userId)
                    .then(response => response.json())
                    .then(data => {
                        let html = '';
                        if (data.length > 0) {
                            html = '<h4>User Events</h4><ul class="list-group">';
                            data.forEach(event => {
                                const statusClass = {
                                    'pending': 'warning',
                                    'completed': 'success',
                                    'cancelled': 'danger'
                                }[event.status] || 'secondary';
                                
                                html += `
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        ${event.title} (${new Date(event.start).toLocaleDateString()})
                                        <span class="badge bg-${statusClass}">${event.status}</span>
                                    </li>
                                `;
                            });
                            html += '</ul>';
                        } else {
                            html = '<p>This user has no events.</p>';
                        }
                        
                        document.getElementById('event-details').innerHTML = html;
                        new bootstrap.Modal(document.getElementById('eventModal')).show();
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    });
    // View Event Details
        const viewEventButtons = document.querySelectorAll('.view-event');
        viewEventButtons.forEach(button => {
            button.addEventListener('click', function() {
                const eventId = this.getAttribute('data-id');
                
                fetch('get_event_details.php?id=' + eventId)
                    .then(response => response.json())
                    .then(data => {
                        let html = `
                            <h4>${data.title}</h4>
                            <p>${data.description || 'No description'}</p>
                            <p><strong>Start:</strong> ${new Date(data.start).toLocaleString()}</p>
                            <p><strong>End:</strong> ${new Date(data.end).toLocaleString()}</p>
                            <p><strong>Location:</strong> ${data.location || 'No location'}</p>
                            <p><strong>Status:</strong> ${data.status}</p>
                            <p><strong>Repeat Type:</strong> ${data.repeat_type}</p>
                            <p><strong>Created By:</strong> ${data.username}</p>
                        `;
                        
                        document.getElementById('event-details').innerHTML = html;
                        new bootstrap.Modal(document.getElementById('eventModal')).show();
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
        
        // Change Event Status
        const changeStatusButtons = document.querySelectorAll('.change-status');
        changeStatusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const eventId = this.getAttribute('data-id');
                const currentStatus = this.getAttribute('data-status');
                
                document.getElementById('status-event-id').value = eventId;
                document.getElementById('event-status').value = currentStatus;
                
                new bootstrap.Modal(document.getElementById('statusModal')).show();
            });
        });
        
        // Save Status Change
        document.getElementById('save-status').addEventListener('click', function() {
            const eventId = document.getElementById('status-event-id').value;
            const newStatus = document.getElementById('event-status').value;
            
            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `event_id=${eventId}&status=${newStatus}&change_event_status=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating status');
            });
        });

        // View User Events
        const viewUserEventsButtons = document.querySelectorAll('.view-user-events');
        viewUserEventsButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                
                fetch('get_user_events.php?user_id=' + userId)
                    .then(response => response.json())
                    .then(data => {
                        let html = '';
                        if (data.length > 0) {
                            html = '<h4>User Events</h4><ul class="list-group">';
                            data.forEach(event => {
                                const statusClass = {
                                    'pending': 'warning',
                                    'completed': 'success',
                                    'cancelled': 'danger'
                                }[event.status] || 'secondary';
                                
                                html += `
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        ${event.title} (${new Date(event.start).toLocaleDateString()})
                                        <span class="badge bg-${statusClass}">${event.status}</span>
                                    </li>
                                `;
                            });
                            html += '</ul>';
                        } else {
                            html = '<p>This user has no events.</p>';
                        }
                        
                        document.getElementById('event-details').innerHTML = html;
                        new bootstrap.Modal(document.getElementById('eventModal')).show();
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    </script>
</body>
</html>