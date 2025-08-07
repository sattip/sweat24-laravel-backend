# Admin Panel - Full User Profile API Documentation

## Overview
This document describes the new API endpoint for retrieving complete user profile data including guardian details, medical history (EMS), and referral information.

## Endpoint Details

### URL
```
GET /api/admin/users/{userId}/full-profile
```

### Authentication
- **Required**: Yes
- **Type**: Bearer Token (Sanctum)
- **Role**: Admin only

### Parameters
- **userId** (path parameter): The ID of the user to retrieve

## Response Structure

### Successful Response (200 OK)
```json
{
  "success": true,
  "data": {
    "id": 45,
    "full_name": "Αντώνης Παπαδόπουλος",
    "email": "ant@example.com",
    "is_minor": true,
    "registration_date": "2025-08-07",
    "signature_url": "https://sweat24.obs.com.gr/storage/signatures/user_45_user.png",
    "guardian_details": {
      "full_name": "Δημήτρης Παπαδόπουλος",
      "father_name": "Ιωάννης Παπαδόπουλος",
      "mother_name": "Ελένη Παπαδοπούλου",
      "birth_date": "1980-05-15",
      "id_number": "ΑΒ123456",
      "phone": "+306912345678",
      "address": "Αγίου Όρους 15",
      "city": "Αθήνα",
      "zip_code": "11145",
      "email": "dimitris@example.com",
      "consent_date": "2025-08-05T12:00:00.000Z",
      "signature_url": "https://sweat24.obs.com.gr/storage/signatures/user_45_guardian.jpg"
    },
    "medical_history": {
      "has_ems_interest": true,
      "ems_contraindications": {
        "Βηματοδότης": {
          "has_condition": true,
          "year_of_onset": "2021"
        },
        "Πυρετός, οξείες βακτηριακές ή ιογενείς λοιμώξεις": {
          "has_condition": false,
          "year_of_onset": null
        },
        "Καρδιολογικές παθήσεις": {
          "has_condition": true,
          "year_of_onset": null
        }
      },
      "ems_liability_accepted": true,
      "other_medical_data": {
        "medical_conditions": {
          "medical_history": "Previous conditions text",
          "emergency_contact": "Emergency Contact Name",
          "emergency_phone": "+30123456789"
        },
        "emergency_contact": {
          "name": "Emergency Contact Name",
          "phone": "+30123456789"
        }
      }
    },
    "found_us_via": {
      "source": "Σύσταση",
      "referrer_info": {
        "referrer_id": 456,
        "referrer_name": "Μαρία Νικολάου",
        "code_or_name_used": "MN456"
      },
      "sub_source": null
    }
  }
}
```

## Field Descriptions

### Root Level Fields
- **id**: User's unique identifier
- **full_name**: User's complete name
- **email**: User's email address
- **is_minor**: Boolean indicating if user was minor at registration
- **registration_date**: Date of user registration (YYYY-MM-DD format)
- **signature_url**: URL to user's signature image (if exists)

### Guardian Details (only if is_minor = true)
- **full_name**: Guardian's complete name
- **father_name**: Father's full name
- **mother_name**: Mother's full name
- **birth_date**: Guardian's birth date (YYYY-MM-DD)
- **id_number**: Guardian's ID/passport number
- **phone**: Guardian's phone number
- **address**: Street address
- **city**: City name
- **zip_code**: Postal code
- **email**: Guardian's email address
- **consent_date**: ISO 8601 timestamp of consent
- **signature_url**: URL to guardian's signature image

### Medical History (only if has_ems_interest = true)
- **has_ems_interest**: Boolean indicating EMS interest
- **ems_contraindications**: Object with medical conditions
  - Each key is a condition name
  - Each value contains:
    - **has_condition**: Boolean
    - **year_of_onset**: Year or null
- **ems_liability_accepted**: Boolean for liability acceptance
- **other_medical_data**: Additional medical information

### Found Us Via
- **source**: How the user found the gym (e.g., "Σύσταση", "Social", etc.)
- **referrer_info**: (Only if source is referral)
  - **referrer_id**: ID of the referring user
  - **referrer_name**: Name of the referrer
  - **code_or_name_used**: Referral code or name used
- **sub_source**: (Only if source is "Social")
  - Contains the specific platform (e.g., "Facebook", "Instagram")

## Conditional Logic

The API implements the following conditional logic:

1. **guardian_details**: Only returned if `is_minor = true`
2. **medical_history**: Only returned if `ems_interest = true`
3. **referrer_info**: Only returned if source is "Σύσταση" or similar referral type
4. **sub_source**: Only returned if source is "Social"

## Error Responses

### 404 Not Found
```json
{
  "success": false,
  "message": "Ο χρήστης δεν βρέθηκε"
}
```

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Unauthorized. Admin role required."
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "Παρουσιάστηκε σφάλμα κατά την ανάκτηση του προφίλ χρήστη"
}
```

## Signature Handling

The API automatically handles signature conversion:
1. If signatures are stored as Base64 strings, they are converted to files
2. Files are stored in `storage/signatures/` directory
3. URLs are returned in the format: `https://domain.com/storage/signatures/user_{id}_{type}.{ext}`
4. Types: "user" for user signature, "guardian" for guardian signature
5. Extensions: png, jpg based on the original format

## Implementation Notes

1. **Performance**: The endpoint uses eager loading for relationships to minimize database queries
2. **Security**: Only admins can access this endpoint
3. **Null Safety**: All fields handle null values gracefully
4. **Date Formats**: Dates use ISO 8601 format for consistency

## Testing

A test script is available at `test-full-profile-endpoint.php` to verify the endpoint functionality.

## Usage Example

```javascript
// JavaScript/Axios example
const getUserFullProfile = async (userId) => {
  try {
    const response = await axios.get(
      `/api/admin/users/${userId}/full-profile`,
      {
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'Accept': 'application/json'
        }
      }
    );
    
    const profile = response.data.data;
    
    // Display user info
    console.log('User:', profile.full_name);
    
    // Check for guardian details
    if (profile.guardian_details) {
      console.log('Guardian:', profile.guardian_details.full_name);
    }
    
    // Check for EMS contraindications
    if (profile.medical_history?.ems_contraindications) {
      for (const [condition, data] of Object.entries(profile.medical_history.ems_contraindications)) {
        if (data.has_condition) {
          console.log(`Has condition: ${condition}`);
        }
      }
    }
    
    // Check referral source
    if (profile.found_us_via?.referrer_info) {
      console.log('Referred by:', profile.found_us_via.referrer_info.referrer_name);
    }
    
  } catch (error) {
    console.error('Error fetching profile:', error);
  }
};
```

## Contact

For any questions or issues regarding this API endpoint, please contact the backend development team.