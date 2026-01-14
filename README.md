# S-RCS (Server Reporting and Controlling System)

## 🌟 Introduction

**S-RCS** is a comprehensive web-based portal designed to revolutionize Windows Active Directory management. By moving administration from the traditional interface to a streamlined web platform, S-RCS significantly reduces time loss and enhances operational efficiency.

The system is built on a core philosophy of **speed and simplicity**: virtually every administrative task—from creating Users, Groups, and Organizational Units (OUs) to managing complex assignments—can be completed in just **3 clicks**.

### 🎯 Key Objectives
- **Minimize Time Loss**: Streamlined workflows replace complex navigation.
- **"3-Click" Efficiency**: Optimized UX design ensures rapid execution of tasks.
- **Centralized Management**: Handle User creation, Group management, OU structuring, and movements from a single dashboard.

## 🚀 Key Features

*   **🌍 Multi-language Support**: The platform is fully multilingual, allowing administrators to work in their preferred language for better accessibility and ease of use.
*   **⚡ Rapid Deployment**: Leverages Docker technology to get the entire system up and running in **under 2 minutes**.
*   **🛡️ Secure & Robust**: Built with security best practices, ensuring safe handling of AD credentials and providing detailed activity logging.

## 📦 Installation & Setup

Get S-RCS running securely in your environment with a few simple commands.

### 1. Prerequisites
- **Docker & Docker Compose** installed.
- Network access to your Active Directory Domain Controller.
- **Active Directory Configuration**:
    - **🔥 Firewall**: Ensure **Port 636 (LDAPS)** is OPEN on the Domain Controller.
    - **🔐 Certificates**: The **Active Directory Certificate Services** role must be active.
    - **🛠️ Required Roles**: You must install:
        - **Certification Authority**
        - **Certification Authority Web Enrollment**
    - *Note: Without these, the secure LDAPS connection will fail.*

### 2. Deployment
Clone the repository and start the containers:
```bash
docker-compose up -d --build
```
*The system will initialize typically within 2 minutes.*

### 3. Visual Installation Wizard
Once the containers are running, navigate to `https://localhost:8080` (or your configured port). The comprehensive installation wizard will guide you through the setup process:

| **1. Welcome & License** | **2. System Requirements** |
| :---: | :---: |
| ![Welcome Screen](www/PH/1.png) | ![Requirements Check](www/PH/2.png) |
| *Accept terms and start* | *Validates server environment* |

| **3. Database Configuration** | **4. Administrator Setup** |
| :---: | :---: |
| ![Database Setup](www/PH/3.png) | ![Admin Account](www/PH/4.png) |
| *Create local admin* |

| **5. Active Directory** | **6. Completion** |
| :---: | :---: |
| ![AD Config](www/PH/5.png) | ![Finish](www/PH/6.png) |
| *LDAP Connection Details* | *Setup Complete!* |

## 🔐 Login & Dashboard

### 7. Secure Login
Access the system securely using your credentials. The login screen is designed for quick and safe authentication.

![Login Screen](www/PH/7.png)

### 8. Interactive Dashboard
Upon login, you are greeted with a comprehensive dashboard providing real-time statistics and quick access to all modules.

![System Dashboard](www/PH/8.png)

## 👥 User Management

S-RCS provides a powerful and intuitive interface for complete user lifecycle management, allowing you to perform complex tasks in seconds.

### Comprehensive Control
Manage every aspect of user accounts with ease:
*   **Create & Edit**: Quickly onboard new users or update existing profiles with a user-friendly form.
*   **Detailed Views**: Access comprehensive user information, including attributes, contact info, and group memberships.
*   **Group Assignment**: Easily add or remove users from Security and Distribution groups without navigating deep menus.
*   **OU Management**: Clear visualization of organization structure and simple movement of users between Organizational Units.

![User Management Interface](www/PH/9.png)

### Security & Lifecycle Actions
Perform critical security actions directly from the dashboard:
*   **Password Management**: Reset passwords and force secure password changes on next login.
*   **Account Status**: Instantly **Lock/Unlock** or **Activate/Deactivate** accounts to maintain security.
*   **Deletion**: Safely remove user accounts when needed.

| **User Details** | **Action Menu** |
| :---: | :---: |
| ![User Details View](www/PH/10.png) | ![Actions Menu](www/PH/11.png) |
| *View full profile* | *Quick administrative actions* |

## 🏢 Organizational Unit (OU) Management

Efficiently structure your Active Directory environment with comprehensive OU management tools.

### Complete OU Lifecycle
*   **Create OUs**: Rapidly provision new Organizational Units to structure your users and resources.
*   **Detailed Insights**: View comprehensive details of any OU, including all contained **Users**, **Groups**, and **Computers**.
*   **Edit & Move**: Rename OUs or restructure your hierarchy by moving OUs to different parents with simple actions.

| **Create & Manage** | **Detailed Contents** |
| :---: | :---: |
| ![OU Creation](www/PH/13.png) | ![OU Details](www/PH/12.png) |
| *Create new OUs instantly* | *Explore users & groups within* |

| **Hierarchy Management** | **Advanced Actions** |
| :---: | :---: |
| ![OU Structure](www/PH/14.png) | ![OU Actions](www/PH/15.png) |
| *Visualize tree structure* | *Edit, Move, or Delete* |

## 👥 Group Management

Simplify the complexity of permission and access management with robust Group tools.

### Group Administration
*   **Create Groups**: Easily create **Security** and **Distribution** groups with specific scopes.
*   **Member Management**: Add or remove users from groups instantly. Search for users and add them in bulk.
*   **Details & Insights**: View full group details, including member lists, descriptions, and parent OUs.
*   **Move & Edit**: Seamlessly move groups between OUs or modify their properties.

![Group Management](www/PH/16.png)

### Member Assignment
Visual interface for managing group composition:
*   **Add Members**: Intuitive search-and-add functionality.
*   **Remove Members**: Quick removal of users from the group list.

| **Group Details** | **Add/Remove Members** |
| :---: | :---: |
| ![Group Details](www/PH/17.png) | ![Group Members](www/PH/18.png) |
| *View composition* | *Manage access rights* |

## 💻 Computer Management

Monitor and organize your domain-joined resources effectively.

### Device Administration
*   **Inventory Views**: Access a complete list of computers joined to the domain.
*   **Object Management**: View detailed attributes of computer objects.
*   **OU Movements**: Easily move computers between Organizational Units to apply different Group Policies.

| **Computer List** | **Computer Details** |
| :---: | :---: |
| ![Computer Inventory](www/PH/19.png) | ![Computer Move](www/PH/20.png) |
| *List of all domain computers* | *View details & move objects* |

## 📜 Group Policy (GPO) Management

Gain visibility into your Group Policy Objects to ensure compliance and security configuration.

### Policy Insights
*   **GPO Inventory**: View a complete list of all Group Policy Objects in your environment.
*   **Detailed Analysis**: Inspect the specific settings, scopes, and linked OUs for each GPO.
*   **Status Monitoring**: Quickly identify disabled or unlinked policies.

| **GPO List** | **GPO Details** |
| :---: | :---: |
| ![GPO List](www/PH/21.png) | ![GPO Settings](www/PH/22.png) |
| *Inventory of all policies* | *Deep dive into policy settings* |

## 📊 Reporting & Analytics

Turn data into actionable insights with powerful one-click reporting.

### One-Click Exports
Generate detailed reports for any resource type in seconds. Ideal for auditing, compliance, and inventory management.
*   **Supported Resources**: Users, Groups, Computers, Organizational Units (OUs), and GPOs.
*   **Flexible Formats**: Export data immediately to **Excel (.xlsx)** or **CSV** formats.
*   **Efficiency**: No complex queries needed—just click and download.

![Reporting Interface](www/PH/23.png)

## 📝 Audit Logging

Maintain complete transparency and accountability across your organization with the integrated audit system.

### Activity Tracking
Every modification and administrative action within the portal is meticulously recorded.
*   **Who**: Identifies the administrator responsible for the action.
*   **What**: Details the specific operation performed (e.g., "User Created", "Group Modified").
*   **When**: Precise timestamping for every event.
*   **Details**: Captures relevant context and success/failure status.

![Audit Logs](www/PH/24.png)

## ⚙️ System Configuration

Complete control over the platform's behavior and settings from a centralized administration hub.

### Central Administration
Manage all system-wide configurations, including:
*   **General Settings**: Application defaults and preferences.
*   **AD Configuration**: Domain controller connections and base DN settings.
*   **Security Policies**: Password rules and access controls.
*   **Customization**: Interface options and language settings.

| **Admin Panel** | **General Settings** |
| :---: | :---: |
| ![Configuration Overview](www/PH/25.png) | ![Main Settings](www/PH/26.png) |
| *Central control panel* | *Core system configuration* |

| **Advanced Options** | **Environment Setup** | **Security** |
| :---: | :---: | :---: |
| ![Advance Config](www/PH/27.png) | ![Environment](www/PH/28.png) | ![Security](www/PH/29.png) |
| *Detailed system tuning* | *Environment parameters* | *Security & Access* |

---

### ⚠️ License & Disclaimer

**© 2025 Ali Zeynalli. All Rights Reserved.**

**S-RCS (Server Reporting and Controlling System)** is the sole intellectual property of **Ali Zeynalli**.

*   This software is **FREE** for standard usage and is **NOT FOR SALE**.
*   **No License Fees**: You can download, install, and use this system freely.
*   **Support Services**: Fees are only applicable for **professional installation assistance** and **training sessions** if requested.
