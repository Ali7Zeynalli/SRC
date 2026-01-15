# Changelog

All notable changes to S-RCS will be documented in this file.

---

## [1.3.0] - 2026-01-15

### ✨ New Features
- 🎫 **Task Management (Helpdesk)** module added
  - Create, edit, and delete support tickets
  - Assign tickets to administrators
  - Status workflow: New → Assigned → In Progress → Resolved → Closed
  - Public comments and internal notes
  - Category management (Hardware, Software, Network, etc.)
- 👤 **Affected User Integration** - Link tickets directly to AD users
  - Search and select affected users from Active Directory
  - Display detailed user info (OU, Groups, Email)
  - Edit affected user in existing tickets
- 📝 **Full Audit Logging** - All ticket actions logged to Activity Logs
  - TICKET_CREATE - when a new ticket is created
  - TICKET_UPDATE - when ticket details are modified
  - TICKET_DELETE - when a ticket is removed
  - TICKET_ASSIGN - when ticket is assigned to someone
  - TICKET_STATUS - when status changes
  - TICKET_COMMENT - when comments/notes are added

### 🔧 Improvements
- Enhanced user search with display name and username
- Improved modal UI for ticket creation and editing
- Merged all SQL schemas into single `schema.sql` for cleaner installation

### 📚 Documentation
- Added Task Management section to README.md
- Added Tapşırıq İdarəetməsi section to README_AZ.md
- Created CHANGELOG.md for version tracking
- Added "What's New" section to both READMEs
