<?php
// task_categories.php - Manage Task Categories
session_start();
require_once 'includes/functions.php';
require_once 'includes/classes/Task.php';

// Check login and admin status
if (!isset($_SESSION['ad_username'])) {
    header('Location: index.php');
    exit;
}

try {
    $ldap_conn = getLDAPConnection();
    if (!isAdminUser($ldap_conn, $_SESSION['ad_username'])) {
        header('Location: tasks.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: tasks.php');
    exit;
}

$page_title = __('manage_categories');
$activePage = 'tasks'; // Keep 'tasks' active in sidebar
$taskObj = new Task();
$success_msg = '';
$error_msg = '';

// Check for flash messages
if (isset($_SESSION['flash_success'])) {
    $success_msg = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $error_msg = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            $name = sanitizeInput($_POST['name']);
            $color = sanitizeInput($_POST['color']);
            
            if ($_POST['action'] === 'add') {
                $taskObj->addCategory($name, $color);
                $_SESSION['flash_success'] = __('category_added');
            } elseif ($_POST['action'] === 'edit') {
                $id = sanitizeInput($_POST['id'], 'int');
                $taskObj->updateCategory($id, $name, $color);
                $_SESSION['flash_success'] = __('category_updated');
            } elseif ($_POST['action'] === 'delete') {
                $id = sanitizeInput($_POST['id'], 'int');
                $taskObj->deleteCategory($id);
                $_SESSION['flash_success'] = __('category_deleted');
            }
            
            // Post-Redirect-Get
            header("Location: task_categories.php");
            exit;
        }
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
        // For errors, we might not redirect if we want to preserve form data, 
        // but for simplicity and consistency, let's flash error too
        $_SESSION['flash_error'] = $error_msg;
        header("Location: task_categories.php");
        exit;
    }
}

$categories = $taskObj->getCategories();

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
                        <a href="tasks.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left"></i> <?php echo __('back_to_tasks'); ?>
                        </a>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus"></i> <?php echo __('add_category'); ?>
                        </button>
                    </div>
                </div>



                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th><?php echo __('category_name'); ?></th>
                                        <th><?php echo __('category_color'); ?></th>
                                        <th><?php echo __('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td><?php echo $cat['id']; ?></td>
                                            <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo htmlspecialchars($cat['color']); ?>">
                                                    <?php echo __('color_' . htmlspecialchars($cat['color'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info btn-circle edit-cat" 
                                                        data-id="<?php echo $cat['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($cat['name']); ?>" 
                                                        data-color="<?php echo htmlspecialchars($cat['color']); ?>"
                                                        data-bs-toggle="modal" data-bs-target="#editCategoryModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline delete-form">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger btn-circle">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('add_category'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('category_name'); ?></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('category_color'); ?></label>
                        <select class="form-select" name="color">
                            <option value="primary"><?php echo __('color_primary'); ?></option>
                            <option value="secondary"><?php echo __('color_secondary'); ?></option>
                            <option value="success"><?php echo __('color_success'); ?></option>
                            <option value="danger"><?php echo __('color_danger'); ?></option>
                            <option value="warning"><?php echo __('color_warning'); ?></option>
                            <option value="info"><?php echo __('color_info'); ?></option>
                            <option value="dark"><?php echo __('color_dark'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('save'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('edit_category'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('category_name'); ?></label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('category_color'); ?></label>
                        <select class="form-select" name="color" id="edit_color">
                            <option value="primary"><?php echo __('color_primary'); ?></option>
                            <option value="secondary"><?php echo __('color_secondary'); ?></option>
                            <option value="success"><?php echo __('color_success'); ?></option>
                            <option value="danger"><?php echo __('color_danger'); ?></option>
                            <option value="warning"><?php echo __('color_warning'); ?></option>
                            <option value="info"><?php echo __('color_info'); ?></option>
                            <option value="dark"><?php echo __('color_dark'); ?></option>
                        </select>
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
    // Fill Edit Modal
    const editBtns = document.querySelectorAll('.edit-cat');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_color').value = this.dataset.color;
        });
    });

    // Toast Notification logic
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    <?php if ($success_msg): ?>
    Toast.fire({
        icon: 'success',
        title: '<?php echo $success_msg; ?>'
    });
    <?php endif; ?>

    <?php if ($error_msg): ?>
    Toast.fire({
        icon: 'error',
        title: '<?php echo $error_msg; ?>'
    });
    <?php endif; ?>

    // Confirmation Dialog
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: '<?php echo __('confirm_delete'); ?>',
                text: "<?php echo __('confirm_delete_text_warning'); ?>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<?php echo __('yes_delete'); ?>',
                cancelButtonText: '<?php echo __('no_cancel'); ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
