<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
  
session_start();
require_once('includes/functions.php');

if (!isset($_SESSION['ad_username'])) {
    header('Location: login.php');
    exit;
}


$pageTitle = __('reports');
$activePage = 'reports';
require_once('includes/header.php');
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once('includes/sidebar.php'); ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 bg-light">
            <!-- Notifications Container - Add this div -->
            <div id="notifications-container" class="position-fixed top-0 start-0 p-3" style="z-index: 1100;"></div>
            
            <!-- Page Title -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                 <h1 class="h2">
                    <i class="fas fa-file-alt me-2"></i><?php echo __('reports'); ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                  
                </div>
            </div>
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                      
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Report Generator Card -->
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-plus-circle me-2 text-primary"></i><?php echo __('create_new_report'); ?>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <!-- Add Report Format Selection -->
                            <div class="list-group list-group-flush">
                                <div class="list-group-item">
                                    <div class="mb-2 fw-bold"><?php echo __('select_format'); ?>:</div>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="reportFormat" id="formatCsv" value="csv" checked>
                                        <label class="btn btn-outline-primary" for="formatCsv">
                                            <i class="fas fa-file-csv me-2"></i>CSV
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="reportFormat" id="formatExcel" value="excel">
                                        <label class="btn btn-outline-success" for="formatExcel">
                                            <i class="fas fa-file-excel me-2"></i>Excel
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="list-group list-group-flush" id="reportSections">
                                <label class="list-group-item d-flex align-items-center">
                                    <input type="checkbox" name="sections[]" value="users" class="form-check-input me-3">
                                    <i class="fas fa-users text-primary me-2"></i><?php echo __('users'); ?>
                                </label>
                                <label class="list-group-item d-flex align-items-center">
                                    <input type="checkbox" name="sections[]" value="groups" class="form-check-input me-3">
                                    <i class="fas fa-layer-group text-success me-2"></i><?php echo __('groups'); ?>
                                </label>
                                <label class="list-group-item d-flex align-items-center">
                                    <input type="checkbox" name="sections[]" value="computers" class="form-check-input me-3">
                                    <i class="fas fa-desktop text-info me-2"></i><?php echo __('computers'); ?>
                                </label>
                                <label class="list-group-item d-flex align-items-center">
                                    <input type="checkbox" name="sections[]" value="ous" class="form-check-input me-3">
                                    <i class="fas fa-sitemap text-warning me-2"></i><?php echo __('organizational_units'); ?>
                                </label>
                                <label class="list-group-item d-flex align-items-center">
                                    <input type="checkbox" name="sections[]" value="gpos" class="form-check-input me-3">
                                    <i class="fas fa-cogs text-danger me-2"></i><?php echo __('group_policy_objects'); ?>
                                </label>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <button type="button" id="generateReport" class="btn btn-primary w-100">
                                <i class="fas fa-file-export me-2"></i><?php echo __('generate_report'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Generated Reports Card -->
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history me-2 text-primary"></i><?php echo __('generated_reports'); ?>
                                </h5>
                                <div id="reportsCount" class="badge bg-primary rounded-pill"></div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?php echo __('generation_date'); ?></th>
                                            <th class="text-end"><?php echo __('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="reportsTable">
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-4">
                                                <i class="fas fa-spinner fa-spin me-2"></i><?php echo __('loading_reports'); ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>
<!-- SweetAlert2 əlavə edirik -->
<script src="temp/assets/lib/SweetAlert2/sweetalert2.min.js"></script>
<link rel="stylesheet" href="temp/css/reports.css">
<script>

document.addEventListener('DOMContentLoaded', function() {
    const generateBtn = document.getElementById('generateReport');
    
    loadReportHistory();
    
    generateBtn.addEventListener('click', async function() {
        const sections = Array.from(document.querySelectorAll('#reportSections input:checked'))
            .map(cb => cb.value);
        
        const format = document.querySelector('input[name="reportFormat"]:checked').value;
            
        if (sections.length === 0) {
            showAlert('warning', '<?php echo __('select_at_least_one_section'); ?>');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i><?php echo __('generating'); ?>...';
        
        try {
            const response = await fetch('api/generate-report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sections, format })
            });

            if (!response.ok) throw new Error('<?php echo __('network_response_not_successful'); ?>');
            
            // Try parsing the response as JSON
            let data;
            const responseText = await response.text();
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('<?php echo __('invalid_json_response'); ?>:', responseText);
                throw new Error('<?php echo __('server_error_response'); ?>: ' + responseText.substring(0, 100) + '...');
            }
            
            if (data.success) {
                // Clear all checkboxes
                document.querySelectorAll('#reportSections input[type="checkbox"]')
                    .forEach(checkbox => checkbox.checked = false);
                    
                await loadReportHistory();
                showAlert('success', `<?php echo __('report_generated_success'); ?> ${format.toUpperCase()}`);
            } else {
                throw new Error(data.error || '<?php echo __('report_generation_failed'); ?>');
            }
        } catch (error) {
            console.error('<?php echo __('error'); ?>:', error);
            showAlert('danger', error.message);
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-file-export me-2"></i><?php echo __('generate_report'); ?>';
        }
    });
});

function deleteReport(filename) {
    Swal.fire({
        title: '<?php echo __('delete_report'); ?>',
        html: `
            <div class="text-center">
                <p class="mb-2"><?php echo __('confirm_delete_report'); ?></p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo __('action_cannot_be_undone'); ?>
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash me-2"></i><?php echo __('delete'); ?>',
        cancelButtonText: '<i class="fas fa-times me-2"></i><?php echo __('cancel'); ?>',
        reverseButtons: true,
        allowOutsideClick: false,
        showClass: {
            popup: 'animate__animated animate__fadeInDown animate__faster'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOutUp animate__faster'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);

            fetch('api/delete-report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ filename: filename }),
                signal: controller.signal
            })
            .then(response => {
                clearTimeout(timeoutId);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    loadReportHistory();
                    showAlert('success', '<?php echo __('report_deleted_success'); ?>');
                } else {
                    throw new Error(data.error || '<?php echo __('report_deletion_failed'); ?>');
                }
            })
            .catch(error => {
                if (error.name === 'AbortError') {
                    showAlert('warning', '<?php echo __('request_timeout_retry'); ?>');
                } else {
                    showAlert('danger', error.message);
                }
            });
        }
    });
}

async function loadReportHistory() {
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout

        const response = await fetch('api/list-reports.php', {
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) throw new Error('<?php echo __('failed_to_load_reports'); ?>');
        
        // Try parsing the response as JSON
        let data;
        const responseText = await response.text();
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('<?php echo __('invalid_json_response'); ?>:', responseText);
            throw new Error('<?php echo __('server_error_response'); ?>');
        }
        
        // Prevent memory leaks by removing old event listeners
        const oldButtons = document.querySelectorAll('[onclick^="deleteReport"]');
        oldButtons.forEach(btn => {
            btn.onclick = null;
        });

        const tbody = document.getElementById('reportsTable');
        
        if (!data.reports || data.reports.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="2" class="text-center text-muted py-4">
                        <i class="fas fa-file-alt fa-2x mb-3 d-block"></i>
                        <?php echo __('no_reports_generated_yet'); ?>
                    </td>
                </tr>`;
            document.getElementById('reportsCount').textContent = '0 <?php echo __('reports'); ?>';
            return;
        }

        tbody.innerHTML = data.reports.map(report => {
            const ext = report.filename.split('.').pop().toLowerCase();
            const isExcel = ext === 'xlsx' || ext === 'xls';
            
            // Get report type from filename
            const nameParts = report.filename.split('_');
            const reportType = nameParts[1] || '<?php echo __('report'); ?>'; // ADReport_Users_date -> Users
            
            return `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="fas ${isExcel ? 'fa-file-excel text-success' : 'fa-file-csv text-primary'} fa-lg me-2"></i>
                            <div>
                                <div>${reportType} <?php echo __('report'); ?> - ${report.formatted_date}</div>
                                <div class="small text-muted">
                                    ${formatFileSize(report.size)} • 
                                    <span class="badge ${isExcel ? 'bg-success' : 'bg-primary'}">${ext.toUpperCase()}</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="btn-group">
                            <a href="api/download-report.php?file=${encodeURIComponent(report.filename)}" 
                               class="btn btn-sm ${isExcel ? 'btn-outline-success' : 'btn-outline-primary'}"
                               title="<?php echo __('download_report'); ?>">
                                <i class="fas fa-download me-1"></i><?php echo __('download'); ?>
                            </a>
                            <button onclick="deleteReport('${report.filename}')" 
                                    class="btn btn-sm btn-outline-danger"
                                    title="<?php echo __('delete_report'); ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        document.getElementById('reportsCount').textContent = 
            `${data.reports.length} <?php echo __('reports'); ?>${data.reports.length !== 1 ? '' : ''}`;
            
    } catch (error) {
        if (error.name === 'AbortError') {
            console.log('<?php echo __('request_timed_out'); ?>');
            showAlert('warning', '<?php echo __('request_timeout_retry'); ?>');
            setTimeout(() => loadReportHistory(), 1000); // Retry after 1 second
        } else {
            console.error('<?php echo __('error_loading_reports'); ?>:', error);
                showAlert('danger', '<?php echo __('failed_to_load_reports'); ?>');
        }
    }
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + " B";
    else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + " KB";
    else return (bytes / 1048576).toFixed(1) + " MB";
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('az-AZ');
}

function showAlert(type, message) {
    const icons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle',
        'danger': 'fas fa-exclamation-circle'
    };
    
    const colors = {
        'success': '#198754',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#0dcaf0',
        'danger': '#dc3545'
    };
    
    const icon = icons[type === 'danger' ? 'error' : type];
    const color = colors[type === 'danger' ? 'error' : type];
    const swalType = type === 'danger' ? 'error' : type;
    
    Swal.fire({
        html: `<div class="d-flex align-items-center">
                <div class="me-3" style="font-size: 1.5rem; color: ${color};">
                    <i class="${icon}"></i>
                </div>
                <div class="text-start">
                    <div style="font-weight: 500;">${message}</div>
                </div>
              </div>`,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        background: '#fff',
        padding: '1rem',
        customClass: {
            popup: 'swal-notification',
        },
        showClass: {
            popup: 'animate__animated animate__fadeInRight animate__faster'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOutRight animate__faster'
        }
    });
}

</script>

