<?php
 /*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */

// Config faylını əlavə et
$config = require(__DIR__ . '/../config/config.php');
$version = $config['installation']['version'] ?? '1.0.0';

?>
        </main>
    </div>
</div>

<footer class="footer mt-auto py-2 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-4 text-start">
                <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-2">
                    <span class="text-muted small">
                        <i class="fas fa-copyright me-1"></i><?php echo __('footer_copyright'); ?>
                    </span>
                  
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="btn-group">
                    <a href="contact.php" class="btn btn-outline-primary btn-sm py-1 px-2">
                        <i class="fas fa-envelope me-1"></i>
                        <?php echo __('footer_contact'); ?>
                    </a>
                    &nbsp; &nbsp;
                    <a href="feedback.php" class="btn btn-outline-primary btn-sm py-1 px-2">
                        <i class="fas fa-comment-alt me-1"></i>
                        <?php echo __('footer_feedback'); ?>
                    </a>
                    &nbsp; &nbsp;
                    <a href="https://ali7zeynalli.github.io/SRCS/docs.html#intro" target="_blank" class="btn btn-outline-info btn-sm py-1 px-2">
                        <i class="fas fa-book me-1"></i>
                        <?php echo __('footer_docs'); ?>
                    </a>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <a href="https://linkedin.com/in/ali7zeynalli" target="_blank" class="text-decoration-none">
                    <span class="text-primary small">
                        <i class="fas fa-external-link-alt me-1"></i>
                        <?php echo __('footer_website'); ?>
                    </span>
                    &nbsp; &nbsp;
                </a>
                <span class="text-muted small version-badge">
                    <i class="fas fa-code-branch me-1"></i><?php echo __('footer_version'); ?><?php echo htmlspecialchars($version); ?>
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- Update styles to match system theme -->
<link href="temp/css/footer.css" rel="stylesheet">

<!-- JavaScript faylları -->
<script src="temp/assets/lib/bootstrap/bootstrap.bundle.min.js"></script>
<script src="temp/assets/lib/jquery/jquery.min.js"></script>
<script src="temp/assets/lib/popper/popper.min.js"></script>
</body>
</html>