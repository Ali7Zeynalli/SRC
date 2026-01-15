<?php
// tasks.php - Task Management Dashboard
session_start();
require_once 'includes/functions.php';
require_once 'includes/classes/Task.php';

// Check login
if (!isset($_SESSION['ad_username'])) {
    header('Location: index.php');
    exit;
}

$page_title = __('helpdesk_tasks');
$activePage = 'tasks';
$taskObj = new Task();

// Handle Create Task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_task') {
    $subject = sanitizeInput($_POST['subject']);
    $description = sanitizeInput($_POST['description']);
    $priority = sanitizeInput($_POST['priority']);
    $category_id = sanitizeInput($_POST['category_id'], 'int');
    
    // Auto-detect requester from AD
    $requester_dn = $_SESSION['user_dn'] ?? ''; 
    $requester_name = $_SESSION['ad_username'];
    
    // Affected User (Optional)
    $affected_user_dn = !empty($_POST['affected_user_dn']) ? sanitizeInput($_POST['affected_user_dn']) : null;
    $affected_user_name = !empty($_POST['affected_user_name']) ? sanitizeInput($_POST['affected_user_name']) : null;
    
    $taskId = $taskObj->create($subject, $description, $priority, $category_id, $requester_dn, $requester_name, $_SESSION['ad_username'], $affected_user_dn, $affected_user_name);
    header("Location: task_details.php?id=$taskId");
    exit;
}


$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filters = [];
if ($filter === 'my') {
    $filters['assigned_to'] = $_SESSION['ad_username'];
} elseif ($filter === 'open') {
    $filters['status'] = 'New'; // Simplified
}

$tasks = $taskObj->getAll($filters);
$stats = $taskObj->getStats();

// Get Categories for Modal
$db = Database::getInstance();
$categories = $db->query("SELECT * FROM task_categories")->fetchAll();

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="main-wrapper">
        <?php require_once('includes/sidebar.php'); ?>
        
        <main>
            <div class="dashboard-container">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $page_title; ?></h1>
                </div>

                <div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?php echo __('total_tickets'); ?></div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total']; ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-clipboard-list fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1"><?php echo __('open_unresolved'); ?></div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['open']; ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-exclamation-circle fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"><?php echo __('unassigned'); ?></div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['unassigned']; ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-user-clock fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Actions -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <a href="tasks.php" class="btn btn-sm <?php echo $filter == 'all' ? 'btn-secondary' : 'btn-outline-secondary'; ?>"><?php echo __('all_tasks'); ?></a>
        <a href="tasks.php?filter=my" class="btn btn-sm <?php echo $filter == 'my' ? 'btn-primary' : 'btn-outline-primary'; ?>"><?php echo __('my_tasks'); ?></a>
        <a href="tasks.php?filter=open" class="btn btn-sm <?php echo $filter == 'open' ? 'btn-info' : 'btn-outline-info'; ?>"><?php echo __('open_tasks'); ?></a>
    </div>
    <div class="d-flex">
        <?php 
        $isAdmin = false;
        try {
            $ldap_conn = getLDAPConnection();
            if (isAdminUser($ldap_conn, $_SESSION['ad_username'])) {
                $isAdmin = true;
            }
        } catch (Exception $e) {
            // Ignore error, assume not admin
        }
        
        if($isAdmin): ?>
            <a href="task_categories.php" class="btn btn-outline-secondary shadow-sm me-2">
                <i class="fas fa-tags fa-sm"></i> <?php echo __('manage_categories'); ?>
            </a>
        <?php endif; ?>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createTaskModal">
            <i class="fas fa-plus fa-sm text-white-50"></i> <?php echo __('create_new_ticket'); ?>
        </button>
    </div>
</div>

<!-- Tasks Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><?php echo __('task_list'); ?></h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th style="width: 5%">ID</th>
                        <th><?php echo __('ticket_subject'); ?></th>
                        <th><?php echo __('ticket_category'); ?></th>
                        <th><?php echo __('ticket_requester'); ?></th>
                        <th><?php echo __('ticket_assigned_to'); ?></th>
                        <th><?php echo __('ticket_priority'); ?></th>
                        <th><?php echo __('ticket_status'); ?></th>
                        <th><?php echo __('ticket_created'); ?></th>
                        <th style="width: 10%"><?php echo __('ticket_actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr><td colspan="9" class="text-center"><?php echo __('no_tasks_found'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach($tasks as $task): ?>
                            <tr>
                                <td>#<?php echo $task['id']; ?></td>
                                <td>
                                    <a href="task_details.php?id=<?php echo $task['id']; ?>" class="font-weight-bold text-decoration-none">
                                        <?php echo htmlspecialchars($task['subject']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $task['category_color'] ?? 'secondary'; ?>">
                                        <?php echo htmlspecialchars($task['category_name'] ?? 'General'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($task['requester_name']); ?></td>
                                <td>
                                    <?php if($task['assigned_to']): ?>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 10px;">
                                                <?php echo strtoupper(substr($task['assigned_to'], 0, 2)); ?>
                                            </div>
                                            <?php echo htmlspecialchars($task['assigned_to']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted font-italic"><?php echo __('unassigned'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $pClass = 'secondary';
                                    if ($task['priority'] == 'High') $pClass = 'warning';
                                    if ($task['priority'] == 'Critical') $pClass = 'danger';
                                    if ($task['priority'] == 'Low') $pClass = 'success';
                                    ?>
                                    <span class="badge bg-<?php echo $pClass; ?>"><?php echo $task['priority']; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $sClass = 'secondary';
                                    if ($task['status'] == 'New') $sClass = 'info';
                                    if ($task['status'] == 'In_Progress') $sClass = 'primary';
                                    if ($task['status'] == 'Resolved') $sClass = 'success';
                                    ?>
                                    <span class="badge bg-<?php echo $sClass; ?>"><?php echo __('status_' . $task['status']); ?></span>
                                </td>
                                <td><small><?php echo date('M d, H:i', strtotime($task['created_at'])); ?></small></td>
                                <td>
                                    <a href="task_details.php?id=<?php echo $task['id']; ?>" class="btn btn-primary btn-sm btn-circle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="tasks.php">
            <input type="hidden" name="action" value="create_task">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('create_new_ticket'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Affected User Search -->
                    <div class="mb-3 position-relative">
                        <label class="form-label"><?php echo __('affected_user'); ?></label>
                        <input type="text" class="form-control" id="affectedUserSearch" placeholder="<?php echo __('search_user_placeholder'); ?>" autocomplete="off">
                        <input type="hidden" name="affected_user_dn" id="affectedUserDn">
                        <input type="hidden" name="affected_user_name" id="affectedUserName">
                        <div id="userSearchResults" class="list-group position-absolute w-100 shadow" style="display:none; z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('ticket_subject'); ?></label>
                        <input type="text" class="form-control" name="subject" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('ticket_category'); ?></label>
                            <select class="form-select" name="category_id" required>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('ticket_priority'); ?></label>
                            <select class="form-select" name="priority">
                                <option value="Low"><?php echo __('priority_Low'); ?></option>
                                <option value="Medium" selected><?php echo __('priority_Medium'); ?></option>
                                <option value="High"><?php echo __('priority_High'); ?></option>
                                <option value="Critical"><?php echo __('priority_Critical'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('ticket_description'); ?></label>
                        <textarea class="form-control" name="description" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('create_ticket'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('affectedUserSearch');
    const resultsDiv = document.getElementById('userSearchResults');
    const dnInput = document.getElementById('affectedUserDn');
    const nameInput = document.getElementById('affectedUserName');
    
    let debounceTimer;
    
    searchInput.addEventListener('input', function() {
        const query = this.value;
        clearTimeout(debounceTimer);
        
        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }
        
        debounceTimer = setTimeout(() => {
            fetch(`api/search_users.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(user => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action';
                            item.innerHTML = `<strong>${user.displayName}</strong> <small class="text-muted">(${user.username})</small>`;
                            item.onclick = function(e) {
                                e.preventDefault();
                                searchInput.value = user.displayName;
                                dnInput.value = user.dn;
                                nameInput.value = user.username; // Use username for display/logic
                                resultsDiv.style.display = 'none';
                            };
                            resultsDiv.appendChild(item);
                        });
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.style.display = 'none';
                    }
                });
        }, 300);
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== searchInput && e.target !== resultsDiv) {
            resultsDiv.style.display = 'none';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
