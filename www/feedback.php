<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */

// No login required - public feedback page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - S-RCS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4e73df;
            --primary-dark: #2e59d9;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .feedback-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
            padding: 3rem;
        }
        .feedback-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .feedback-icon i {
            font-size: 2rem;
            color: white;
        }
        .btn-feedback {
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-feedback:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .back-link {
            position: absolute;
            top: 1rem;
            left: 1rem;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-link btn btn-light">
        <i class="fas fa-arrow-left me-2"></i>Back
    </a>
    
    <div class="feedback-card text-center">
        <div class="feedback-icon">
            <i class="fas fa-comments"></i>
        </div>
        
        <h2 class="mb-3">We Value Your Feedback</h2>
        <p class="text-muted mb-4">
            Help us improve S-RCS by sharing your thoughts, bug reports, or feature requests.
        </p>
        
        <div class="d-grid gap-3">
            <a href="https://github.com/Ali7Zeynalli/S-RCS/issues/new?template=bug_report.yml" target="_blank" class="btn btn-outline-danger btn-feedback">
                <i class="fas fa-bug me-2"></i>Report a Bug
            </a>
            <a href="https://github.com/Ali7Zeynalli/S-RCS/issues/new?template=feature_request.yml" target="_blank" class="btn btn-outline-warning btn-feedback">
                <i class="fas fa-lightbulb me-2"></i>Request a Feature
            </a>
            <a href="mailto:Ali.Z.Zeynalli@gmail.com?subject=S-RCS%20Feedback" class="btn btn-outline-primary btn-feedback">
                <i class="fas fa-envelope me-2"></i>Send General Feedback
            </a>
            <a href="https://github.com/Ali7Zeynalli/S-RCS/discussions" target="_blank" class="btn btn-dark btn-feedback">
                <i class="fab fa-github me-2"></i>Join GitHub Discussion
            </a>
            <a href="https://ali7zeynalli.github.io/SRCS/docs.html#intro" target="_blank" class="btn btn-outline-info btn-feedback">
                <i class="fas fa-book me-2"></i>Documentation
            </a>
        </div>
        
        <hr class="my-4">
        
        <p class="small text-muted mb-0">
            <i class="fas fa-heart text-danger me-1"></i>
            Thank you for helping us improve!
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>