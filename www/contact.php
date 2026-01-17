<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */

// No login required - public support page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - S-RCS Support</title>
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
        .contact-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
            padding: 3rem;
        }
        .contact-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .contact-icon i {
            font-size: 2rem;
            color: white;
        }
        .btn-contact {
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-contact:hover {
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
    
    <div class="contact-card text-center">
        <div class="contact-icon">
            <i class="fas fa-headset"></i>
        </div>
        
        <h2 class="mb-3">Get in Touch</h2>
        <p class="text-muted mb-4">
            Have questions about S-RCS? Need help with installation or configuration? 
            Reach out to us directly.
        </p>
        
        <div class="d-grid gap-3">
            <a href="mailto:Ali.Z.Zeynalli@gmail.com" class="btn btn-outline-primary btn-contact">
                <i class="fas fa-envelope me-2"></i>Ali.Z.Zeynalli@gmail.com
            </a>
            <a href="https://linkedin.com/in/ali7zeynalli" target="_blank" class="btn btn-primary btn-contact">
                <i class="fab fa-linkedin me-2"></i>LinkedIn Profile
            </a>
            <a href="https://github.com/Ali7Zeynalli/S-RCS" target="_blank" class="btn btn-dark btn-contact">
                <i class="fab fa-github me-2"></i>GitHub Repository
            </a>
            <a href="https://github.com/Ali7Zeynalli/S-RCS/issues/new?template=bug_report.yml" target="_blank" class="btn btn-outline-danger btn-contact">
                <i class="fas fa-bug me-2"></i>Report an Issue
            </a>
            <a href="https://ali7zeynalli.github.io/SRCS/docs.html#intro" target="_blank" class="btn btn-outline-info btn-contact">
                <i class="fas fa-book me-2"></i>Documentation
            </a>
        </div>
        
        <hr class="my-4">
        
        <p class="small text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Professional support services available for installation, training, and customization.
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>