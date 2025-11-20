# Questionnaire Responses - Admin Panel Documentation

## Overview
This documentation covers all API endpoints for viewing and managing questionnaire responses in the admin panel.

## Authentication
All admin endpoints require authentication with Bearer token and admin role:
```
Authorization: Bearer {admin_token}
```

## API Endpoints

### 1. Get All Questionnaire Responses
View all responses across all questionnaires with filtering options.

**Endpoint:**
```
GET /api/v1/questionnaire-responses
```

**Query Parameters:**
- `questionnaire_id` (optional): Filter by specific questionnaire
- `user_id` (optional): Filter by specific user
- `trigger_type` (optional): Filter by trigger type (daily, weekly, etc.)
- `from_date` (optional): Start date for filtering (YYYY-MM-DD)
- `to_date` (optional): End date for filtering (YYYY-MM-DD)
- `completed_only` (optional, default: true): Show only completed responses
- `page` (optional): Page number for pagination
- `per_page` (optional, default: 50): Results per page

**Example Request:**
```bash
curl -X GET "https://api.sweat93.gr/api/v1/questionnaire-responses?questionnaire_id=11&from_date=2025-10-01" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Accept: application/json"
```

**Example Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 2,
        "questionnaire_id": 11,
        "user_id": 79,
        "responses": [
          {
            "question_index": 0,
            "answer": "επιλογη 1"
          }
        ],
        "trigger_type": "daily",
        "completed_at": "2025-10-12T22:37:25.000000Z",
        "session_id": "session_1760297845735",
        "questionnaire": {
          "id": 11,
          "title": "lalala"
        },
        "user": {
          "id": 79,
          "name": "ΜΑΡΙΛΕΝΑ ΠΑΠΑΚΩΝΣΤΑΝΤΙΝΟΥ",
          "email": "papakonstantinoumarilena@gmail.com"
        }
      }
    ],
    "total": 1,
    "per_page": 50,
    "last_page": 1
  }
}
```

### 2. Get Responses for Specific Questionnaire
View all responses for a specific questionnaire.

**Endpoint:**
```
GET /api/v1/questionnaires/{questionnaire_id}/responses
```

**Path Parameters:**
- `questionnaire_id`: The ID of the questionnaire

**Query Parameters:**
- `trigger_type` (optional): Filter by trigger type
- `from_date` (optional): Start date (YYYY-MM-DD)
- `to_date` (optional): End date (YYYY-MM-DD)
- `page` (optional): Page number
- `per_page` (optional, default: 50): Results per page

**Example Request:**
```bash
curl -X GET "https://api.sweat93.gr/api/v1/questionnaires/11/responses" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Accept: application/json"
```

**Example Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 2,
        "questionnaire_id": 11,
        "user_id": 79,
        "responses": [
          {
            "question_index": 0,
            "answer": "επιλογη 1"
          }
        ],
        "trigger_type": "daily",
        "completed_at": "2025-10-12T22:37:25.000000Z",
        "user": {
          "id": 79,
          "name": "ΜΑΡΙΛΕΝΑ ΠΑΠΑΚΩΝΣΤΑΝΤΙΝΟΥ",
          "email": "papakonstantinoumarilena@gmail.com"
        }
      }
    ],
    "total": 1,
    "per_page": 50
  }
}
```

### 3. Get Questionnaire Statistics
View aggregated statistics for a questionnaire.

**Endpoint:**
```
GET /api/v1/questionnaires/{questionnaire_id}/statistics
```

**Path Parameters:**
- `questionnaire_id`: The ID of the questionnaire

**Example Request:**
```bash
curl -X GET "https://api.sweat93.gr/api/v1/questionnaires/11/statistics" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Accept: application/json"
```

**Example Response:**
```json
{
  "success": true,
  "data": {
    "total_responses": 1,
    "responses_by_trigger": {
      "daily": 1,
      "weekly": 0,
      "monthly": 0
    },
    "completion_rate": 1,
    "avg_completion_time": null
  }
}
```

### 4. View Single Response Details
Get detailed information about a specific response.

**Endpoint:**
```
GET /api/v1/questionnaire-responses/{response_id}
```

**Path Parameters:**
- `response_id`: The ID of the response

**Example Request:**
```bash
curl -X GET "https://api.sweat93.gr/api/v1/questionnaire-responses/2" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Accept: application/json"
```

**Example Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "questionnaire_id": 11,
    "user_id": 79,
    "responses": [
      {
        "question_index": 0,
        "answer": "επιλογη 1"
      }
    ],
    "trigger_type": "daily",
    "completed_at": "2025-10-12T22:37:25.000000Z",
    "session_id": "session_1760297845735",
    "questionnaire": {
      "id": 11,
      "title": "lalala",
      "description": null,
      "questions": [
        {
          "question": "lala1",
          "type": "multiple_choice",
          "required": false,
          "options": ["επιλογη 1"]
        }
      ]
    },
    "user": {
      "id": 79,
      "name": "ΜΑΡΙΛΕΝΑ ΠΑΠΑΚΩΝΣΤΑΝΤΙΝΟΥ",
      "email": "papakonstantinoumarilena@gmail.com",
      "phone": "6946633366"
    }
  }
}
```

### 5. Delete Response (Admin Only)
Delete a specific response.

**Endpoint:**
```
DELETE /api/v1/questionnaire-responses/{response_id}
```

**Path Parameters:**
- `response_id`: The ID of the response to delete

**Example Request:**
```bash
curl -X DELETE "https://api.sweat93.gr/api/v1/questionnaire-responses/2" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Accept: application/json"
```

**Example Response:**
```json
{
  "success": true,
  "message": "Response deleted successfully"
}
```

## Data Export Queries

### Export All Responses as CSV
To export responses for analysis, you can use the following query format:

```bash
curl -X GET "https://api.sweat93.gr/api/v1/questionnaire-responses?questionnaire_id=11&from_date=2025-10-01&to_date=2025-10-31&per_page=1000" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Accept: application/json" > responses_export.json
```

### Get Response Summary by User
To see how many times each user has responded:

```sql
SELECT 
    u.id,
    u.name,
    u.email,
    COUNT(qr.id) as total_responses,
    MAX(qr.completed_at) as last_response_date
FROM users u
LEFT JOIN questionnaire_responses qr ON u.id = qr.user_id
WHERE qr.questionnaire_id = 11
GROUP BY u.id, u.name, u.email
ORDER BY total_responses DESC;
```

## Response Data Structure

Each response contains:
- **id**: Unique response ID
- **questionnaire_id**: ID of the questionnaire
- **user_id**: ID of the user who responded
- **responses**: Array of answers with question_index and answer
- **trigger_type**: When/why the questionnaire was triggered (daily, weekly, etc.)
- **completed_at**: Timestamp when response was completed
- **session_id**: Optional session identifier

## Filtering Best Practices

1. **Date Filtering**: Use ISO 8601 format (YYYY-MM-DD)
2. **Pagination**: Default is 50 per page, max 1000
3. **Trigger Types**: Common values are `daily`, `weekly`, `monthly`, `after_lesson`, `after_purchase`

## Admin Panel Integration

For the admin panel frontend, you'll need to:

1. **List View**: Show all responses with filters
2. **Detail View**: Show individual response with all answers
3. **Statistics View**: Display charts and summaries
4. **Export Function**: Allow CSV/Excel export

## Example Admin Panel Code (React)

```javascript
// Fetch all responses
const fetchResponses = async (filters = {}) => {
  const queryParams = new URLSearchParams(filters).toString();
  const response = await fetch(
    `https://api.sweat93.gr/api/v1/questionnaire-responses?${queryParams}`,
    {
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Accept': 'application/json'
      }
    }
  );
  return response.json();
};

// Get statistics
const fetchStatistics = async (questionnaireId) => {
  const response = await fetch(
    `https://api.sweat93.gr/api/v1/questionnaires/${questionnaireId}/statistics`,
    {
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Accept': 'application/json'
      }
    }
  );
  return response.json();
};
```

## Testing Endpoints

Test with actual data:
```bash
# Get responses for questionnaire 11
curl -X GET "https://api.sweat93.gr/api/v1/questionnaires/11/responses" \
  -H "Authorization: Bearer {your_admin_token}" \
  -H "Accept: application/json"

# Get responses from user 79
curl -X GET "https://api.sweat93.gr/api/v1/questionnaire-responses?user_id=79" \
  -H "Authorization: Bearer {your_admin_token}" \
  -H "Accept: application/json"
```

---

**Version:** 1.0  
**Updated:** October 12, 2025  
**Status:** Production Ready