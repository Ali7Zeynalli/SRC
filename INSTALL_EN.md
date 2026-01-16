# S-RCS Installation Guide

> 📘 **Azərbaycan dilində versiya üçün**, see [INSTALL.md](INSTALL.md)

---

## 📋 Table of Contents
1. [Introduction](#introduction)
2. [Requirements](#requirements)
3. [Download the Project](#download-the-project)
4. [Active Directory Preparation](#active-directory-preparation)
5. [.env Configuration](#env-configuration)
6. [Docker Installation](#docker-installation)
7. [Starting the Containers](#starting-the-containers)
8. [Installation Wizard](#installation-wizard)
9. [Post-Installation - User/Group Management](#post-installation)
10. [Troubleshooting](#troubleshooting)

---

## Introduction

**S-RCS** (Server Reporting and Controlling System) is a Windows Active Directory management portal. This guide will walk you through the installation process from scratch.

### System Architecture
```
┌─────────────────────────────────────────────────────────────┐
│                    S-RCS Server (Docker)                     │
│  ┌─────────────┐  ┌──────────────┐  ┌───────────────────┐   │
│  │ PHP/Apache  │  │    MySQL     │  │    phpMyAdmin     │   │
│  └─────────────┘  └──────────────┘  └───────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                           │
                           │ LDAPS (Port 636)
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              Windows Server (Domain Controller)              │
│  ┌─────────────┐  ┌──────────────┐  ┌───────────────────┐   │
│  │Active       │  │ Certificate  │  │   Users, Groups   │   │
│  │Directory DS │  │  Services    │  │   OUs, Computers  │   │
│  └─────────────┘  └──────────────┘  └───────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## Requirements

### S-RCS Server Requirements
| Component | Minimum | Recommended |
|-----------|---------|-------------|
| RAM | 2 GB | 4 GB |
| CPU | 2 Core | 4 Core |
| Disk | 10 GB | 20 GB |
| OS | Windows 10/11, Ubuntu 20.04+, macOS | Ubuntu 22.04 LTS |

### Windows Server (Domain Controller) Requirements
- Windows Server 2016, 2019, 2022, or 2025
- **Active Directory Domain Services** role installed
- **Active Directory Certificate Services** role installed
- **Port 636 (LDAPS)** must be open

---

## Download the Project

### Using Git (Recommended)

```bash
git clone https://github.com/Ali7Zeynalli/S-RCS.git
cd S-RCS
```

### As ZIP

1. Go to the [GitHub Page](https://github.com/Ali7Zeynalli/S-RCS)
2. Click the green **Code** button
3. Select **Download ZIP**
4. Extract the ZIP file and navigate to the folder

---

## Active Directory Preparation

> ⚠️ **Important**: These steps must be performed on the Domain Controller!

### Step 1: Install Certificate Services Role

1. Open **Server Manager**
2. Select **Manage → Add Roles and Features**
3. Choose **Role-based installation**
4. Select the server and click **Next**
5. Check **Active Directory Certificate Services**

![Server Roles Selection](www/PH/role.png)

6. Select the following sub-roles:
   - ✅ **Certification Authority**
   - ✅ **Certification Authority Web Enrollment**
7. Complete the installation

### Step 2: Certificate Authority Configuration

1. You will see a yellow warning in Server Manager
2. Click **Configure Active Directory Certificate Services**
3. Confirm credentials (Domain Admin account)
4. Select **Certification Authority** and **CA Web Enrollment**:

![Certificate Services Configuration](www/PH/cer.png)

5. Select **Enterprise CA**
6. Select **Root CA**
7. Select **Create a new private key**
8. Keep the default cryptographic settings
9. Set the CA name (e.g., `DOMAIN-CA`)
10. Validity period: 5 years (default)
11. Complete the installation

### Step 3: LDAPS Verification

> 🔐 **What is Port 636 (LDAPS)?**
> 
> LDAPS = LDAP over **SSL/TLS** - a secure encrypted connection.
> - **Port 389** = Unencrypted LDAP (insecure, do not use!)
> - **Port 636** = SSL-protected LDAPS (secure ✅)
> 
> S-RCS only works with **Port 636** because passwords and sensitive data are transmitted.

In PowerShell:
```powershell
# Check the LDAPS port
Test-NetConnection -ComputerName localhost -Port 636

# Result should be: TcpTestSucceeded : True
```

![LDAPS Port Test Result](www/PH/prot%20test.png)

### Step 4: Firewall Configuration

> ⚠️ **If you see `TcpTestSucceeded : False`**, the port is blocked and needs to be opened!

#### Method 1: Using PowerShell (Recommended)

```powershell
# Open port 636
New-NetFirewallRule -Name "LDAPS" -DisplayName "LDAPS (636)" -Protocol TCP -LocalPort 636 -Action Allow -Direction Inbound

# Verify the rule was created
Get-NetFirewallRule -Name "LDAPS"
```

#### Method 2: Using Windows Firewall UI

1. Open **Windows Defender Firewall with Advanced Security**:
   - Press `Win + R` → type `wf.msc` → Enter
2. Select **Inbound Rules** in the left panel
3. Click **New Rule...** in the right panel
4. **Rule Type**: Select `Port` → Next
5. **Protocol and Ports**: `TCP`, **Specific local ports**: `636` → Next
6. **Action**: `Allow the connection` → Next
7. **Profile**: Check all profiles → Next
8. **Name**: `LDAPS (636)` → Finish

#### Verification

```powershell
Test-NetConnection -ComputerName localhost -Port 636
# Now TcpTestSucceeded should be: True
```

---

## .env Configuration

Open the `.env` file in the project folder and change the passwords:

```bash
# MySQL Database Settings
MYSQL_ROOT_PASSWORD=StrongPassword123!     # 🔴 Change this!
MYSQL_DATABASE=ldap_auth                   # You can keep this
MYSQL_USER=srcs_admin                      # You can keep this
MYSQL_PASSWORD=YourPasswordHere!           # 🔴 Change this!

# MySQL Port
MYSQL_PORT=3306

# Web Server Ports
HTTP_PORT=8080                             # Change if needed
HTTPS_PORT=8043                            # Change if needed

# phpMyAdmin Port
PMA_PORT=8081
```

> ⚠️ **Important**: You must change the default passwords!

---

## Docker Installation

> 💡 If Docker is already installed, skip to [Starting the Containers](#starting-the-containers).

### Windows 10/11 Desktop

1. Download [Docker Desktop](https://www.docker.com/products/docker-desktop)
2. Install and restart
3. Open Docker Desktop
4. Settings → General → Enable "Use the WSL 2 based engine"
5. Verify:
   ```powershell
   docker --version
   docker-compose --version
   ```

### Ubuntu / Debian Linux

```bash
# Install Docker
sudo apt update
sudo apt install -y docker.io docker-compose

# Add user to docker group
sudo usermod -aG docker $USER

# Log out and log back in
exit
```

### macOS

1. Download [Docker Desktop for Mac](https://www.docker.com/products/docker-desktop)
2. Open the `.dmg` file and drag to Applications
3. Start Docker Desktop
4. Verify in Terminal:
   ```bash
   docker --version
   docker-compose --version
   ```

### Windows Server 2019/2022

PowerShell (Administrator):
```powershell
# Install Containers feature
Install-WindowsFeature -Name Containers

# Install Docker
Install-Module -Name DockerMsftProvider -Force
Install-Package -Name docker -ProviderName DockerMsftProvider -Force

# Restart
Restart-Computer

# Verify
docker version
```

---

## Starting the Containers

In the project folder:

```bash
# Build and start
docker-compose up -d --build

# Check status
docker-compose ps
```

**Expected result:**
```
NAME          STATUS
mysql-db      Up
php-apache    Up
php-myadmin   Up
```

### Wait for MySQL to be Ready

MySQL initializes the database on first start (1-2 minutes):

```bash
docker-compose logs -f mysql
# Wait until you see "ready for connections"
```

---

## Installation Wizard

### Access the Web Installer

Open in browser:
- **HTTPS**: `https://localhost:8043`
- **HTTP**: `http://localhost:8080`

> 💡 **Which address should I use?**
> 
> | Where is Docker running? | Address to use |
> |--------------------------|----------------|
> | On your own computer | `localhost` or `127.0.0.1` |
> | On another server (Windows/Linux) | Server's IP address, e.g., `192.168.1.50` |
> | Cloud/VPS (AWS, Azure, DigitalOcean) | Public IP or domain name |
> 
> **Example**: If Docker is running on an Ubuntu server with IP `192.168.1.100` → `https://192.168.1.100:8043`

### Step 1: System Requirements

The installer will automatically check:
- ✅ PHP Version (7.4+)
- ✅ LDAP Extension
- ✅ PDO Extension
- ✅ MySQL Extension
- ✅ OpenSSL Extension
- ✅ Config Directory (Writable)
- ✅ Memory Limit (128M+)

### Step 2: Domain Settings

| Field | Description | Example |
|-------|-------------|---------|
| Domain Controller IP | IP address of the DC | `192.168.1.10` |
| Domain Name | Domain name | `company.local` |
| LDAPS Port | 636 (default) | `636` |
| Admin Username | Domain Admin | `administrator` |
| Admin Password | Admin password | `****` |
| Admin Group | Management group | `Administrators` |

> ⚠️ **Important**: Set the **Admin Group** correctly! Users in this group will be able to log into S-RCS.

![Installer - Admin Group](www/PH/install%20group.png)

### Step 3: Database Settings

> 📌 These fields are automatically loaded from the `.env` file and are read-only.

### Step 4: Confirmation

Review all settings and click **Start Installation**.

### Step 5: Installation Complete

When installation is complete:
- **License Key** will be displayed (save it!)

### Step 6: Archive Installation Files (Uninstall Wizard)

After successful installation, the **Uninstall Wizard** page will appear. This doesn't **delete** the installer files, it **archives** them to the `_archive` folder.

> 🔐 **Why is this necessary?**
> 
> For security reasons, installer files should not remain on a production system. Otherwise:
> - Someone could access install.php again
> - The system could be reconfigured
> - A security risk could arise

**Files to be archived:**

| File | Description |
|------|-------------|
| `install.php` | Installation wizard UI |
| `installer.php` | Backend installation operations |
| `preinstall.php` | Pre-checks |
| `install.sql` | SQL scripts |
| `uninstall.php` | Archive utility (archives itself too) |

**How it works:**

1. Type **"UNINSTALL"** (in capital letters)
2. Click the **Archive Files** button
3. Files will be moved to `www/_archive/`
4. You will be automatically redirected to the **Login** page

> 💡 **Need to reinstall?** Move the files from the `_archive` folder back to the `www/` folder.

---

## Post-Installation

### User and Group Management

After S-RCS is installed, if you want to grant access to additional users and groups:

#### Adding New Groups

1. Log into S-RCS with an admin account
2. Go to **System Configuration** → **Active Directory Settings**
3. Add new groups in the **Allowed Groups** section

![Active Directory Settings - Allowed Groups](www/PH/ad%20group.png)

#### Examples

| Group | Purpose |
|-------|---------|
| `Domain Admins` | Full access - management of entire domain |
| `Administrators` | Server administrators |
| `Help Desk` | Support team - ticket management |
| `S-RCS Admins` | Custom group created for S-RCS |

> 💡 **Note**: Each group must exist in AD. To create a new group, open **Active Directory Users and Computers**.

---

## 🌐 Remote Access - NovusGate VPN

> **Want secure access to S-RCS from anywhere?**

If you want to manage the S-RCS system outside the office - from home, while traveling, or from another city - we recommend **NovusGate** VPN solution.

### What is NovusGate?

**NovusGate** is a modern VPN solution based on WireGuard protocol that allows secure remote connection to your company network.

### Benefits

| Feature | Description |
|---------|-------------|
| 🔐 **Secure Encryption** | Military-grade encryption with WireGuard |
| ⚡ **Fast Connection** | Connect in milliseconds |
| 🌍 **Access Anywhere** | Access S-RCS from home, cafe, or while traveling |
| 📱 **Cross-Platform** | Windows, Linux, macOS, Android, iOS support |

### More Information

🔗 **GitHub**: [github.com/Ali7Zeynalli/NovusGate](https://github.com/Ali7Zeynalli/NovusGate)

> 💡 Contact us for NovusGate installation support.

---

## Troubleshooting

### MySQL Won't Start

**Error**: "The designated data directory /var/lib/mysql/ is unusable"

**Solution**:
```bash
docker-compose down
rm -rf mysql/*          # Windows: Remove-Item -Recurse mysql\*
docker-compose up -d --build
```

### LDAPS Connection Error

**Error**: "Can't contact LDAP server"

**Check**:
1. Is port 636 open? `Test-NetConnection DC_IP -Port 636`
2. Is Certificate Services installed?
3. Does the firewall allow it?
4. Are the username and password correct?

### Container Logs

```bash
docker-compose logs              # All logs
docker-compose logs php-apache   # Apache only
docker-compose logs mysql        # MySQL only
```

### Port Conflicts

1. Change ports in the `.env` file
2. Restart the containers

---

## 🤝 Professional Support / Enterprise Support

> **Finding installation difficult?** We can help!

If you cannot perform the steps shown in this guide yourself or need full enterprise-level support, you can contact us:

### Paid Services

| Service | Description |
|---------|-------------|
| 🛠️ **Full Installation** | Complete installation of S-RCS in your infrastructure |
| 🔧 **AD Configuration** | Certificate Services, LDAPS, Firewall configuration |
| 📞 **Technical Support** | Problem resolution and ongoing support |
| 📚 **Training** | S-RCS usage training for your team |

> 💰 **Pricing**: Service fees are calculated individually based on the scope and complexity of work. Contact us for a free consultation.

### Contact

📧 **Email**: Ali.Z.Zeynalli@gmail.com  
💼 **LinkedIn**: [linkedin.com/in/ali7zeynalli](https://linkedin.com/in/ali7zeynalli)  
📱 **Phone**: +49 152 2209 4631 (WhatsApp)

> 💼 SLA (Service Level Agreement) support is available for enterprise customers.

### 🌍 Supported Languages

| Language | Dil |
|----------|-----|
| 🇦🇿 Azerbaijani | Azərbaycan |
| 🇬🇧 English | İngilis |
| 🇩🇪 German | Alman |
| 🇷🇺 Russian | Rus |
| 🇹🇷 Turkish | Türk |

---

*© 2025 Ali Zeynalli - S-RCS Installation Guide*