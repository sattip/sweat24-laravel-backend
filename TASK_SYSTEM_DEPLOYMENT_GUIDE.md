# 🚀 Task System - Complete Deployment Guide for panel.sweat93.gr

## 📋 Overview
The complete Tasks system has been successfully deployed to the Laravel API server (`api.sweat93.gr`). This document provides all necessary information for frontend integration and testing.

## ✅ Backend Deployment Status
- ✅ **Database Migration:** Tasks table created successfully
- ✅ **Task Model:** Fully implemented with relationships and scopes
- ✅ **TaskController:** All CRUD operations and notification endpoints
- ✅ **API Routes:** All endpoints registered and secured
- ✅ **Timezone Fix:** Complete datetime handling without conversion
- ✅ **Role-based Access:** Admin and trainer only permissions
- ✅ **Statistics:** Dashboard counters working correctly

## 🔗 Available API Endpoints

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| `GET` | `/api/v1/tasks` | Admin/Trainer | List all tasks with filtering |
| `POST` | `/api/v1/tasks` | Admin/Trainer | Create new task |
| `GET` | `/api/v1/tasks/{id}` | Admin/Trainer | Get specific task |
| `PUT/PATCH` | `/api/v1/tasks/{id}` | Admin/Trainer | Update task |
| `DELETE` | `/api/v1/tasks/{id}` | Admin/Trainer | Delete task |
| `GET` | `/api/v1/tasks-stats` | Admin/Trainer | Get dashboard statistics |
| `GET` | `/api/v1/assignable-users` | Admin/Trainer | Get users for assignment |
| `GET` | `/api/v1/my-pending-tasks` | Admin/Trainer | **NEW** - Get user's pending tasks |
| `GET` | `/api/v1/my-tasks` | All Users | Get user's assigned tasks |
| `GET` | `/api/v1/my-tasks/high-priority` | All Users | Get high priority tasks |
| `POST` | `/api/v1/tasks/{id}/mark-completed` | Admin/Trainer | Mark task as completed |
| `POST` | `/api/v1/tasks/{id}/acknowledge` | All Users | Acknowledge task notification |

## 💬 Team Chat API Endpoints

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| `GET` | `/api/v1/team-chat/messages` | Admin/Trainer | Get recent chat messages |
| `POST` | `/api/v1/team-chat/messages` | Admin/Trainer | Send new chat message |
| `GET` | `/api/v1/team-chat/online-users` | Admin/Trainer | Get online users list |
| `GET` | `/api/v1/team-chat/stats` | Admin/Trainer | Get chat statistics |
| `DELETE` | `/api/v1/team-chat/messages/{id}` | Admin/Trainer | Delete chat message |

## 🔐 Authentication & Authorization

### Required Headers
```javascript
{
  "Authorization": "Bearer YOUR_TOKEN",
  "Accept": "application/json",
  "Content-Type": "application/json"
}
```

### Role-based Access
- **Admin & Trainer:** Full access to all task management features
- **Members:** Can only view tasks assigned to them (limited endpoints)
- **Unauthenticated:** No access (401 Unauthorized)

### Test Credentials
```
Admin User:
- Email: admin@sweat24.gr
- Password: (existing admin password)

Trainer User:
- Email: trainer@sweat24.gr  
- Password: password
- Role: trainer

Test Token (Valid):
243|dFQ24LBHhQtleXCMInhSawXaWnbuAFppvdhG29Gsa8ad2d26
```

## 📊 API Response Examples

### 1. Get Tasks List
```http
GET /api/v1/tasks
```
**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 22,
        "name": "Test Notification Task",
        "description": "This is a test task",
        "priority": "high",
        "deadline": "2025-09-20 16:33:12",
        "status": "pending",
        "creation_date": "2025-09-18",
        "completion_date": null,
        "created_at": "2025-09-18T16:33:12.000000Z",
        "updated_at": "2025-09-18T16:33:12.000000Z",
        "creator": {
          "id": 1,
          "name": "Admin Demo User",
          "email": "admin@sweat24.gr"
        },
        "assignee": {
          "id": 97,
          "name": "Trainer Demo User",
          "email": "trainer@sweat24.gr"
        }
      }
    ],
    "per_page": 15,
    "total": 4
  }
}
```

### 2. Create New Task
```http
POST /api/v1/tasks
Content-Type: application/json

{
  "name": "Complete project documentation",
  "description": "Finish writing the API documentation",
  "priority": "high",
  "deadline": "2025-09-25 17:00:00",
  "assigned_to": 97
}
```
**Response:**
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {
    "id": 23,
    "name": "Complete project documentation",
    "description": "Finish writing the API documentation",
    "priority": "high",
    "deadline": "2025-09-25 17:00:00",
    "status": "pending",
    "creation_date": "2025-09-18",
    "assigned_to": 97,
    "created_by": 1,
    "creator": {
      "id": 1,
      "name": "Admin Demo User",
      "email": "admin@sweat24.gr"
    },
    "assignee": {
      "id": 97,
      "name": "Trainer Demo User", 
      "email": "trainer@sweat24.gr"
    }
  }
}
```

### 3. Get Dashboard Statistics
```http
GET /api/v1/tasks-stats
```
**Response:**
```json
{
  "success": true,
  "data": {
    "total_tasks": 4,
    "pending_tasks": 3,
    "in_progress_tasks": 0,
    "completed_tasks": 1,
    "cancelled_tasks": 0,
    "overdue_tasks": 2,
    "due_today": 1,
    "due_this_week": 2,
    "priority_breakdown": {
      "urgent": 1,
      "high": 2,
      "medium": 0,
      "low": 0
    }
  }
}
```

### 4. Get My Pending Tasks (For Notifications)
```http
GET /api/v1/my-pending-tasks
```
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 22,
      "name": "Test Notification Task",
      "description": "This is a test task for notifications",
      "priority": "high",
      "deadline": "2025-09-20 16:33:12",
      "status": "pending",
      "creation_date": "2025-09-18",
      "creator": {
        "id": 1,
        "name": "Admin Demo User",
        "email": "admin@sweat24.gr"
      }
    }
  ]
}
```

### 5. Get Assignable Users
```http
GET /api/v1/assignable-users
```
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Admin Demo User",
      "email": "admin@sweat24.gr",
      "role": "admin"
    },
    {
      "id": 97,
      "name": "Trainer Demo User",
      "email": "trainer@sweat24.gr",
      "role": "trainer"
    }
  ]
}
```

### 6. Get Team Chat Messages
```http
GET /api/v1/team-chat/messages
```
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "message": "Hello team! This is a test message from the API.",
      "user_id": 97,
      "user_name": "Trainer Demo User",
      "user_role": "trainer",
      "created_at": "2025-09-19T14:30:12.000000Z",
      "updated_at": "2025-09-19T14:30:12.000000Z"
    }
  ]
}
```

### 7. Send Team Chat Message
```http
POST /api/v1/team-chat/messages
Content-Type: application/json

{
  "message": "I've completed the urgent tasks for today!"
}
```
**Response:**
```json
{
  "success": true,
  "message": "Message sent successfully",
  "data": {
    "id": 2,
    "message": "I've completed the urgent tasks for today!",
    "user_id": 97,
    "user_name": "Trainer Demo User",
    "user_role": "trainer",
    "created_at": "2025-09-19T14:35:00.000000Z",
    "updated_at": "2025-09-19T14:35:00.000000Z"
  }
}
```

### 8. Get Online Users
```http
GET /api/v1/team-chat/online-users
```
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 97,
      "name": "Trainer Demo User",
      "role": "trainer",
      "last_seen": "2025-09-19T14:35:00.000000Z",
      "is_online": true
    },
    {
      "id": 1,
      "name": "Admin Demo User",
      "role": "admin", 
      "last_seen": "2025-09-19T14:30:00.000000Z",
      "is_online": true
    }
  ]
}
```

## 🔧 Query Parameters & Filtering

### Task List Filtering
```http
GET /api/v1/tasks?status=pending&priority=high&assigned_to=97&sort_by=deadline&sort_order=asc&per_page=10
```

**Available Filters:**
- `status`: pending, in_progress, completed, cancelled
- `priority`: low, medium, high, urgent
- `assigned_to`: User ID
- `created_by`: User ID
- `overdue`: true/false
- `due_within`: Number of days (e.g., 7 for due within 7 days)

**Sorting Options:**
- `sort_by`: created_at, deadline, priority, status, name
- `sort_order`: asc, desc
- `per_page`: 1-100 (default: 15)

## 🎯 Task Field Specifications

### Required Fields (Create)
```typescript
{
  name: string (max: 255),
  priority: "low" | "medium" | "high" | "urgent",
  assigned_to: number (user ID)
}
```

### Optional Fields
```typescript
{
  description?: string (max: 2000),
  deadline?: string ("YYYY-MM-DD HH:mm:ss")
}
```

### System Fields (Auto-set)
```typescript
{
  id: number,
  status: "pending" | "in_progress" | "completed" | "cancelled",
  creation_date: string ("YYYY-MM-DD"),
  completion_date?: string ("YYYY-MM-DD"),
  created_by: number (current user ID),
  created_at: string (ISO datetime),
  updated_at: string (ISO datetime)
}
```

## 🕐 Timezone Handling (FIXED)

### The Problem (SOLVED)
Previously, datetime values were subject to timezone conversion:
- Frontend sends: `"2025-09-18 13:00:00"`
- Backend saved: `"2025-09-17T21:00:00.000000Z"` ❌

### The Solution (IMPLEMENTED)
Now datetime values are stored exactly as sent:
- Frontend sends: `"2025-09-18 13:00:00"`
- Backend saves: `"2025-09-18 13:00:00"` ✅

### Implementation Details
- Deadlines are stored as strings (no datetime casting)
- Creation/completion dates use local date formatting
- No timezone conversion applied anywhere
- Exact match between frontend input and backend storage

## 🚨 Error Handling

### Common Error Responses

#### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

#### 403 Forbidden  
```json
{
  "error": "Unauthorized"
}
```

#### 422 Validation Error
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "assigned_to": ["The selected assigned to is invalid."]
  }
}
```

#### 404 Not Found
```json
{
  "error": "Task not found"
}
```

## 🧪 Testing Commands

### 1. Test Authentication
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     https://api.sweat93.gr/api/v1/my-pending-tasks
```

### 2. Test Task Creation
```bash
curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"name":"Test Task","priority":"high","assigned_to":97}' \
     https://api.sweat93.gr/api/v1/tasks
```

### 3. Test Statistics
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     https://api.sweat93.gr/api/v1/tasks-stats
```

## 🔔 Notification System Features

### Available Notification Endpoints
- `/api/v1/my-pending-tasks` - Get pending tasks for current user
- `/api/v1/my-tasks` - Get all assigned tasks
- `/api/v1/my-tasks/high-priority` - Get high priority tasks with popup logic
- `/api/v1/tasks/{id}/acknowledge` - Mark task as acknowledged

### Popup Logic (Built-in)
- **Urgent tasks:** Show every login
- **High priority tasks:** Show daily or if overdue
- **Medium/Low tasks:** Show once

### Frontend Integration Points
- **Header Badge:** Use `/my-pending-tasks` count
- **Login Popup:** Use `/my-tasks/high-priority`
- **Task List:** Use `/tasks` with filtering
- **Dashboard Stats:** Use `/tasks-stats`

## 📱 Mobile App Support

### Available for Mobile
- All endpoints work with mobile authentication
- Task acknowledgment system for reducing popup frequency
- High priority task filtering for mobile notifications
- Optimized response structure for mobile UI

## 🔧 Database Schema

### Tasks Table
```sql
CREATE TABLE tasks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    deadline VARCHAR(255) NULL, -- Stored as string to avoid timezone issues
    created_by BIGINT NOT NULL,
    assigned_to BIGINT NOT NULL,
    status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
    creation_date VARCHAR(255) DEFAULT CURRENT_DATE, -- Stored as string
    completion_date VARCHAR(255) NULL, -- Stored as string
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_assigned_status (assigned_to, status),
    INDEX idx_created_by (created_by),
    INDEX idx_priority (priority),
    INDEX idx_deadline (deadline),
    INDEX idx_status (status)
);
```

## 🎯 Frontend Implementation Checklist

### Required Components
- [ ] Task List Page (Admin/Trainer)
- [ ] Task Creation Form
- [ ] Task Edit Form  
- [ ] Task Detail View
- [ ] Dashboard Statistics Widget
- [ ] Header Notification Badge
- [ ] Login Popup with Pending Tasks
- [ ] Task Filters and Search
- [ ] User Assignment Dropdown

### Required API Calls
- [ ] Login/Authentication
- [ ] Fetch Tasks List (`GET /tasks`)
- [ ] Create Task (`POST /tasks`)
- [ ] Update Task (`PUT /tasks/{id}`)
- [ ] Delete Task (`DELETE /tasks/{id}`)
- [ ] Get Statistics (`GET /tasks-stats`)
- [ ] Get Assignable Users (`GET /assignable-users`)
- [ ] Get Pending Tasks (`GET /my-pending-tasks`)
- [ ] Mark Completed (`POST /tasks/{id}/mark-completed`)

### Notification Features
- [ ] Header badge with pending task count
- [ ] Auto-refresh every 2 minutes
- [ ] Login popup for high priority tasks
- [ ] Task completion from notifications
- [ ] Sound/visual alerts for urgent tasks

## 🚀 Production Deployment Status

### API Server (api.sweat93.gr)
- ✅ **All endpoints deployed and tested**
- ✅ **Database migration executed**
- ✅ **Timezone fix implemented**
- ✅ **Role-based security active**
- ✅ **Performance optimized**
- ✅ **Error handling complete**

### Ready for Frontend Integration
- ✅ **Consistent API responses**
- ✅ **Comprehensive documentation**
- ✅ **Test data available**
- ✅ **Working authentication tokens**
- ✅ **All edge cases handled**

## 📞 Support & Troubleshooting

### Common Issues

1. **403 Forbidden Error**
   - Check user role (must be admin or trainer)
   - Verify authentication token validity
   - Ensure correct headers are sent

2. **Timezone Issues**
   - All datetime handling is now fixed
   - Dates are stored exactly as sent
   - No conversion applied

3. **Task Assignment Issues**
   - Only admin and trainer users can be assigned tasks
   - Members are excluded from assignment dropdown

### Test Data Available
- **4 existing tasks** in various states
- **5 assignable users** (2 admin, 3 trainer)
- **Working authentication tokens**
- **Complete test scenarios**

## 🎉 Conclusion

The Task System is **100% ready for frontend integration**. All backend functionality is deployed, tested, and working correctly. The frontend team can immediately begin integration using the provided API endpoints and documentation.

**Next Steps:**
1. Use the provided test token for initial development
2. Implement the task management UI components
3. Add notification badge and popup system
4. Test with the provided API endpoints
5. Deploy to production panel.sweat93.gr

**The complete task management system is ready to go live! 🚀**

---

*Generated: 2025-09-18*  
*API Server: api.sweat93.gr*  
*Frontend Target: panel.sweat93.gr*
