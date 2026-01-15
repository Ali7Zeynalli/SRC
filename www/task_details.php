<?php
// task_details.php - Detailed View
session_start();
require_once 'includes/functions.php';
require_once 'includes/classes/Task.php';

if (!isset($_SESSION['ad_username'])) {
    header('Location: index.php');
    exit;
}

// Initialize LDAP connection and fetch data
try {
    $ldap_conn = getLDAPConnection();
    $potentialAssignees = getPotentialAssignees($ldap_conn);
    $isAdmin = isAdminUser($ldap_conn, $_SESSION['ad_username']);
} catch (Exception $e) {
    $error = $e->getMessage();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$taskObj = new Task();
$task = $taskObj->get($id);

if (!$task) {
    die("Task not found.");
}

$page_title = "Ticket #" . $task['id'];
$activePage = 'tasks';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actor = $_SESSION['ad_username'];
    
    if (isset($_POST['add_comment'])) {
        $msg = sanitizeInput($_POST['message']);
        $isInternal = isset($_POST['is_internal']) ? 1 : 0;
        $taskObj->addComment($id, $actor, $msg, $isInternal);
    }
    
    if (isset($_POST['change_status'])) {
        $status = sanitizeInput($_POST['status']);
        $taskObj->updateStatus($id, $status, $actor);
    }
    
    // Assign User Handler
    if (isset($_POST['assign_user'])) {
        $targetUser = sanitizeInput($_POST['assignee']);
        if (!empty($targetUser)) {
             $taskObj->assign($id, $targetUser, $actor);
        }
    }


    
    if (isset($_POST['assign_self'])) {
        $taskObj->assign($id, $actor, $actor);
    }
    
    // Edit Task
    if (isset($_POST['action']) && $_POST['action'] === 'edit_task') {
        $subject = sanitizeInput($_POST['subject']);
        $description = sanitizeInput($_POST['description']);
        $priority = sanitizeInput($_POST['priority']);
        $category_id = sanitizeInput($_POST['category_id'], 'int');
        $affected_user_dn = !empty($_POST['affected_user_dn']) ? sanitizeInput($_POST['affected_user_dn']) : null;
        $affected_user_name = !empty($_POST['affected_user_name']) ? sanitizeInput($_POST['affected_user_name']) : null;
        
        $taskObj->update($id, $subject, $description, $priority, $category_id, $actor, $affected_user_dn, $affected_user_name);
        header("Location: task_details.php?id=$id");
        exit;
    }

    // Delete Task
    if (isset($_POST['action']) && $_POST['action'] === 'delete_task') {
        $taskObj->delete($id);
        $_SESSION['flash_success'] = "Ticket #$id deleted successfully."; 
        header("Location: tasks.php");
        exit;
    }
    
    // Refresh to prevent resubmission
    header("Location: task_details.php?id=$id");
    exit;
}

$history = $taskObj->getHistory($id, true); // Get all including internal
$categories = $taskObj->getCategories(); // Fetch categories for edit modal

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="main-wrapper">
        <?php require_once('includes/sidebar.php'); ?>
        
        <main>
            <div class="dashboard-container">
                 <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $page_title; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="tasks.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> <?php echo __('task_list'); ?>
                        </a>
                    </div>
                </div>

                <div class="row">
    <!-- Left Column: Details -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <span class="badge bg-<?php echo $task['category_color']; ?> me-2"><?php echo $task['category_name']; ?></span>
                    <?php echo htmlspecialchars($task['subject']); ?>
                </h6>
                <div class="dropdown no-arrow">
                    <span class="badge bg-secondary"><?php echo __('status_' . $task['status']); ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="text-muted text-uppercase small font-weight-bold"><?php echo __('ticket_description'); ?></h6>
                    <div class="p-3 bg-light rounded text-dark">
                        <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                    </div>
                </div>

                <hr>

                <!-- Discussion / History -->
                <h6 class="text-muted text-uppercase small font-weight-bold mb-3"><?php echo __('ticket_history'); ?></h6>
                
                <div class="timeline" style="max-height: 500px; overflow-y: auto;">
                    <?php foreach($history as $item): 
                        // Visibility Check for Internal Notes
                        if ($item['is_internal']) {
                            $canView = $isAdmin || 
                                       ($task['assigned_to'] === $_SESSION['ad_username']) || 
                                       ($item['actor_username'] === $_SESSION['ad_username']);
                            if (!$canView) continue;
                        }
                    ?>
                        <div class="d-flex mb-3 <?php echo ($item['is_internal']) ? 'opacity-75' : ''; ?>">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-gray-200 d-flex align-items-center justify-content-center fw-bold text-secondary" style="width: 40px; height: 40px;">
                                    <?php echo strtoupper(substr($item['actor_username'] ?? 'Sys', 0, 2)); ?>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="card <?php echo ($item['is_internal']) ? 'border-warning' : 'border-left-primary'; ?>">
                                    <div class="card-body py-2 px-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0 small fw-bold text-primary">
                                                <?php echo htmlspecialchars($item['actor_username'] ?? 'System'); ?>
                                                <?php if($item['is_internal']): ?>
                                                    <span class="badge bg-warning text-dark ms-2" style="font-size: 0.6rem;"><?php echo __('internal_note'); ?></span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted"><?php echo date('M d, H:i', strtotime($item['created_at'])); ?></small>
                                        </div>
                                        
                                        <?php if($item['action_type'] == 'Comment'): ?>
                                            <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($item['message'])); ?></p>
                                        <?php else: ?>
                                            <p class="mb-0 small text-muted fst-italic">
                                                <i class="fas fa-info-circle me-1"></i> 
                                                <?php echo htmlspecialchars($item['message']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Reply Box -->
                <div class="mt-4 p-3 bg-light rounded">
                    <form method="POST">
                        <div class="mb-2">
                            <textarea class="form-control" name="message" rows="3" placeholder="<?php echo __('reply_placeholder'); ?>" required></textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_internal" id="internalCheck">
                                <label class="form-check-label small text-muted" for="internalCheck">
                                    <i class="fas fa-lock me-1"></i> <?php echo __('internal_note'); ?>
                                </label>
                            </div>
                            <button type="submit" name="add_comment" class="btn btn-primary btn-sm">
                                <i class="fas fa-paper-plane me-1"></i> <?php echo __('send_reply'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Meta & Actions -->
    <div class="col-lg-4">
        <!-- Ticket Info -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?php echo __('ticket_info'); ?></h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted small"><?php echo __('ticket_requester'); ?>:</td>
                        <td class="fw-bold"><?php echo htmlspecialchars($task['requester_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small"><?php echo __('ticket_created'); ?>:</td>
                        <td><?php echo date('Y-m-d H:i', strtotime($task['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small"><?php echo __('ticket_priority'); ?>:</td>
                        <td>
                            <span class="badge bg-<?php echo ($task['priority']=='Critical'?'danger':($task['priority']=='High'?'warning':'success')); ?>">
                                <?php echo __('priority_' . $task['priority']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small"><?php echo __('ticket_assigned_to'); ?>:</td>
                        <td class="fw-bold">
                            <?php if($task['assigned_to']): ?>
                                <i class="fas fa-user-check text-success me-1"></i> <?php echo htmlspecialchars($task['assigned_to']); ?>
                            <?php else: ?>
                                <span class="text-warning"><?php echo __('unassigned'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Actions -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?php echo __('ticket_actions'); ?></h6>
            </div>
            <div class="card-body">
                            <!-- Affected User -->
                            <?php if (!empty($task['affected_user_name'])): ?>
                            <div class="mb-3">
                                <label class="small text-muted mb-1"><?php echo __('affected_user'); ?></label>
                                <?php 
                                $affectedInfo = null;
                                try {
                                    $affectedInfo = getUserDetails($ldap_conn, $task['affected_user_name']);
                                } catch (Exception $e) {
                                    // Fallback to basic info if fetch fails
                                }
                                ?>
                                
                                <?php if ($affectedInfo): ?>
                                    <div class="card bg-light border-left-info shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold text-dark"><?php echo htmlspecialchars($affectedInfo['displayName']); ?></div>
                                                    <div class="small text-muted">@<?php echo htmlspecialchars($affectedInfo['username']); ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="small">
                                                <div class="mb-1">
                                                    <i class="fas fa-envelope text-gray-500 me-2 w-20"></i>
                                                    <?php echo htmlspecialchars($affectedInfo['email'] ?: '-'); ?>
                                                </div>
                                                <div class="mb-1">
                                                    <i class="fas fa-sitemap text-gray-500 me-2 w-20"></i>
                                                    <span class="text-muted"><?php echo htmlspecialchars($affectedInfo['ou']); ?></span>
                                                </div>
                                                <div class="mb-1">
                                                    <i class="fas fa-users text-gray-500 me-2 w-20"></i>
                                                    <span class="text-truncate d-inline-block" style="max-width: 250px; vertical-align: top;" title="<?php echo htmlspecialchars($affectedInfo['groups']); ?>">
                                                        <?php echo htmlspecialchars($affectedInfo['groups'] ?: '-'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Fallback View -->
                                    <div class="d-flex align-items-center p-2 border rounded bg-light">
                                        <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold text-dark"><?php echo htmlspecialchars($task['affected_user_name']); ?></div>
                                            <?php if(!empty($task['affected_user_dn'])): ?>
                                                <small class="text-muted" style="font-size: 0.7em;"><?php echo htmlspecialchars($task['affected_user_dn']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            


                            <!-- Assign User -->
                             <form method="POST" class="mb-3 mt-3">
                    <label class="small text-muted mb-1"><?php echo __('assign_ticket'); ?></label>
                    <div class="input-group">
                        <select class="form-select" name="assignee">
                            <option value=""><?php echo __('select_user'); ?></option>
                            <?php if (!empty($potentialAssignees)): ?>
                                <?php foreach($potentialAssignees as $pa): ?>
                                    <option value="<?php echo htmlspecialchars($pa['username']); ?>" 
                                        <?php echo ($task['assigned_to'] == $pa['username']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pa['display_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="<?php echo $_SESSION['ad_username']; ?>"><?php echo __('assign_self'); ?></option>
                            <?php endif; ?>
                        </select>
                        <button type="submit" name="assign_user" class="btn btn-primary">
                            <i class="fas fa-user-check"></i>
                        </button>
                    </div>
                </form>

                <!-- Edit / Delete Actions -->
                 <hr>
                 <div class="d-grid gap-2">
                    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTaskModal">
                        <i class="fas fa-edit me-2"></i> <?php echo __('edit_ticket'); ?>
                    </button>
                    
                    <form method="POST" class="w-100" id="deleteTaskForm">
                        <input type="hidden" name="action" value="delete_task">
                        <button type="button" class="btn btn-outline-danger w-100 text-start" onclick="confirmDeleteTicket()">
                            <i class="fas fa-trash-alt me-2"></i> <?php echo __('delete_ticket'); ?>
                        </button>
                    </form>
                 </div>

                <!-- Change Status -->
                <form method="POST">
                    <label class="small text-muted mb-1"><?php echo __('update_status'); ?></label>
                    <div class="input-group mb-3">
                        <select class="form-select" name="status">
                            <option value="New" <?php echo $task['status']=='New'?'selected':''; ?>><?php echo __('status_New'); ?></option>
                            <option value="In_Progress" <?php echo $task['status']=='In_Progress'?'selected':''; ?>><?php echo __('status_In_Progress'); ?></option>
                            <option value="Pending_User" <?php echo $task['status']=='Pending_User'?'selected':''; ?>><?php echo __('status_Pending_User'); ?></option>
                            <option value="Resolved" <?php echo $task['status']=='Resolved'?'selected':''; ?>><?php echo __('status_Resolved'); ?></option>
                            <option value="Closed" <?php echo $task['status']=='Closed'?'selected':''; ?>><?php echo __('status_Closed'); ?></option>
                        </select>
                        <button class="btn btn-outline-primary" type="submit" name="change_status">Update</button>
                    </div>
                </form>

             
            </div>
        </div>
    </div>
</div>

            </div>
        </main>
    </div>
</div>



<?php require_once 'includes/footer.php'; ?>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            <input type="hidden" name="action" value="edit_task">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('edit_ticket'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Affected User Search (Edit) -->
                    <div class="mb-3 position-relative">
                        <label class="form-label"><?php echo __('affected_user'); ?></label>
                        <input type="text" class="form-control" id="editAffectedUserSearch" placeholder="<?php echo __('search_user_placeholder'); ?>" autocomplete="off" value="<?php echo htmlspecialchars($task['affected_user_name'] ?? ''); ?>">
                        <input type="hidden" name="affected_user_dn" id="editAffectedUserDn" value="<?php echo htmlspecialchars($task['affected_user_dn'] ?? ''); ?>">
                        <input type="hidden" name="affected_user_name" id="editAffectedUserName" value="<?php echo htmlspecialchars($task['affected_user_name'] ?? ''); ?>">
                        <div id="editUserSearchResults" class="list-group position-absolute w-100 shadow" style="display:none; z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('ticket_subject'); ?></label>
                        <input type="text" class="form-control" name="subject" value="<?php echo htmlspecialchars($task['subject']); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('ticket_category'); ?></label>
                            <select class="form-select" name="category_id" required>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $task['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('ticket_priority'); ?></label>
                            <select class="form-select" name="priority">
                                <option value="Low" <?php echo $task['priority'] == 'Low' ? 'selected' : ''; ?>><?php echo __('priority_Low'); ?></option>
                                <option value="Medium" <?php echo $task['priority'] == 'Medium' ? 'selected' : ''; ?>><?php echo __('priority_Medium'); ?></option>
                                <option value="High" <?php echo $task['priority'] == 'High' ? 'selected' : ''; ?>><?php echo __('priority_High'); ?></option>
                                <option value="Critical" <?php echo $task['priority'] == 'Critical' ? 'selected' : ''; ?>><?php echo __('priority_Critical'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('ticket_description'); ?></label>
                        <textarea class="form-control" name="description" rows="5" required><?php echo htmlspecialchars($task['description']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('save_changes'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Modal User Search Logic
    const editSearchInput = document.getElementById('editAffectedUserSearch');
    const editResultsDiv = document.getElementById('editUserSearchResults');
    const editDnInput = document.getElementById('editAffectedUserDn');
    const editNameInput = document.getElementById('editAffectedUserName');
    
    if (editSearchInput) {
        let debounceTimer;
        
        editSearchInput.addEventListener('input', function() {
            const query = this.value;
            clearTimeout(debounceTimer);
            
            // If cleared manually
            if (query.length === 0) {
                editDnInput.value = '';
                editNameInput.value = '';
                editResultsDiv.style.display = 'none';
                return;
            }
            
            if (query.length < 2) {
                 editResultsDiv.style.display = 'none';
                 return;
            }
            
            debounceTimer = setTimeout(() => {
                fetch(`api/search_users.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        editResultsDiv.innerHTML = '';
                        if (data.length > 0) {
                             editResultsDiv.style.display = 'block';
                             data.forEach(user => {
                                 const item = document.createElement('a');
                                 item.classList.add('list-group-item', 'list-group-item-action');
                                 item.href = '#';
                                 item.innerHTML = `<strong>${user.displayName}</strong> <small class='text-muted'>(${user.username})</small>`;
                                 item.onclick = function(e) {
                                     e.preventDefault();
                                     editSearchInput.value = user.username; // Display username or display_name
                                     editDnInput.value = user.dn;
                                     editNameInput.value = user.username; // Store samaccountname
                                     editResultsDiv.style.display = 'none';
                                 };
                                 editResultsDiv.appendChild(item);
                             });
                        } else {
                             editResultsDiv.style.display = 'none';
                        }
                    })
                    .catch(err => console.error('Error searching users:', err));
            }, 300);
        });
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (editSearchInput && !editSearchInput.contains(e.target) && !editResultsDiv.contains(e.target)) {
                editResultsDiv.style.display = 'none';
            }
        });
    }
});

function confirmDeleteTicket() {
    Swal.fire({
        title: '<?php echo __('confirm_delete'); ?>',
        text: '<?php echo __('confirm_delete_text_warning'); ?>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '<?php echo __('yes_delete'); ?>',
        cancelButtonText: '<?php echo __('no_cancel'); ?>'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteTaskForm').submit();
        }
    });
}
</script>
