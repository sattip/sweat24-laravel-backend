# Frontend Implementation Guide: Minor Registration with Parent Consent

## Overview
This guide provides complete implementation details for adding minor registration support to your frontend application. The backend is ready with all necessary endpoints and validation.

## Backend API Endpoints Reference

### 1. Age Verification Endpoint
```
POST /api/v1/auth/check-age
```

**Request:**
```json
{
  "birth_date": "2010-05-15"  // Format: YYYY-MM-DD
}
```

**Response:**
```json
{
  "is_minor": true,
  "age": 14,
  "server_date": "2025-08-06"
}
```

### 2. Registration with Parent Consent
```
POST /api/v1/auth/register-with-consent
```

**Request for Adult (18+):**
```json
{
  "firstName": "John",
  "lastName": "Doe",
  "email": "john@example.com",
  "password": "securePassword123",
  "birthDate": "1990-05-15",
  "gender": "male",
  "phone": "6901234567",
  "signature": "data:image/png;base64,...",
  "signedAt": "2025-08-06T12:00:00.000Z",
  "documentType": "terms_and_conditions",
  "documentVersion": "1.0",
  "medicalHistory": {
    "medical_conditions": {},
    "current_health_problems": {},
    "prescribed_medications": [],
    "smoking": {},
    "physical_activity": {},
    "emergency_contact": {},
    "liability_declaration_accepted": true,
    "submitted_at": "2025-08-06T12:00:00.000Z"
  }
}
```

**Request for Minor (<18) - Additional Fields:**
```json
{
  // ... all fields from above plus:
  "parentConsent": {
    "parentFullName": "Νικόλαος Παπαδόπουλος",
    "fatherFirstName": "Νικόλαος",
    "fatherLastName": "Παπαδόπουλος",
    "motherFirstName": "Μαρία",
    "motherLastName": "Γεωργίου",
    "parentBirthDate": "1980-03-20",
    "parentIdNumber": "ΑΒ123456",
    "parentPhone": "6909876543",
    "parentLocation": "Αθήνα",
    "parentStreet": "Πανεπιστημίου",
    "parentStreetNumber": "42",
    "parentPostalCode": "10434",
    "parentEmail": "parent@example.com",
    "consentAccepted": true,
    "signature": "data:image/png;base64,..."
  }
}
```

## Frontend Implementation Steps

### Step 1: Update Registration Flow

```typescript
// types/Registration.ts
interface ParentConsent {
  parentFullName: string;
  fatherFirstName: string;
  fatherLastName: string;
  motherFirstName: string;
  motherLastName: string;
  parentBirthDate: string;
  parentIdNumber: string;
  parentPhone: string;
  parentLocation: string;
  parentStreet: string;
  parentStreetNumber: string;
  parentPostalCode: string;
  parentEmail: string;
  consentAccepted: boolean;
  signature: string;
}

interface RegistrationData {
  firstName: string;
  lastName: string;
  email: string;
  password: string;
  birthDate: string;
  gender?: string;
  phone?: string;
  signature: string;
  signedAt: string;
  documentType: string;
  documentVersion: string;
  medicalHistory?: any;
  parentConsent?: ParentConsent; // Only if minor
}
```

### Step 2: Age Verification Service

```typescript
// services/ageVerification.ts
export class AgeVerificationService {
  static async checkAge(birthDate: string): Promise<{
    isMinor: boolean;
    age: number;
    serverDate: string;
  }> {
    const response = await fetch('/api/v1/auth/check-age', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ birth_date: birthDate })
    });

    if (!response.ok) {
      throw new Error('Age verification failed');
    }

    const data = await response.json();
    return {
      isMinor: data.is_minor,
      age: data.age,
      serverDate: data.server_date
    };
  }
}
```

### Step 3: Parent Consent Form Component

```tsx
// components/ParentConsentForm.tsx
import React, { useState } from 'react';
import SignaturePad from 'react-signature-canvas';

interface ParentConsentFormProps {
  onSubmit: (consent: ParentConsent) => void;
  onBack: () => void;
}

export const ParentConsentForm: React.FC<ParentConsentFormProps> = ({
  onSubmit,
  onBack
}) => {
  const [formData, setFormData] = useState<ParentConsent>({
    parentFullName: '',
    fatherFirstName: '',
    fatherLastName: '',
    motherFirstName: '',
    motherLastName: '',
    parentBirthDate: '',
    parentIdNumber: '',
    parentPhone: '',
    parentLocation: '',
    parentStreet: '',
    parentStreetNumber: '',
    parentPostalCode: '',
    parentEmail: '',
    consentAccepted: false,
    signature: ''
  });

  const [errors, setErrors] = useState<Record<string, string>>({});
  const signaturePadRef = React.useRef<SignaturePad>(null);

  const validateForm = (): boolean => {
    const newErrors: Record<string, string> = {};

    // Required field validation
    if (!formData.parentFullName) newErrors.parentFullName = 'Απαιτείται το ονοματεπώνυμο του γονέα';
    if (!formData.fatherFirstName) newErrors.fatherFirstName = 'Απαιτείται το όνομα του πατέρα';
    if (!formData.fatherLastName) newErrors.fatherLastName = 'Απαιτείται το επώνυμο του πατέρα';
    if (!formData.motherFirstName) newErrors.motherFirstName = 'Απαιτείται το όνομα της μητέρας';
    if (!formData.motherLastName) newErrors.motherLastName = 'Απαιτείται το επώνυμο της μητέρας';
    if (!formData.parentBirthDate) newErrors.parentBirthDate = 'Απαιτείται η ημερομηνία γέννησης';
    if (!formData.parentIdNumber) newErrors.parentIdNumber = 'Απαιτείται ο αριθμός ταυτότητας';
    if (!formData.parentPhone) newErrors.parentPhone = 'Απαιτείται το τηλέφωνο';
    if (!formData.parentLocation) newErrors.parentLocation = 'Απαιτείται η πόλη';
    if (!formData.parentStreet) newErrors.parentStreet = 'Απαιτείται η οδός';
    if (!formData.parentStreetNumber) newErrors.parentStreetNumber = 'Απαιτείται ο αριθμός';
    if (!formData.parentPostalCode) newErrors.parentPostalCode = 'Απαιτείται ο ταχυδρομικός κώδικας';
    if (!formData.parentEmail) newErrors.parentEmail = 'Απαιτείται το email';
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (formData.parentEmail && !emailRegex.test(formData.parentEmail)) {
      newErrors.parentEmail = 'Μη έγκυρο email';
    }

    // Postal code validation (5 digits for Greece)
    if (formData.parentPostalCode && formData.parentPostalCode.length !== 5) {
      newErrors.parentPostalCode = 'Ο ταχυδρομικός κώδικας πρέπει να είναι 5 ψηφία';
    }

    // Parent age validation (must be 18+)
    if (formData.parentBirthDate) {
      const birthDate = new Date(formData.parentBirthDate);
      const today = new Date();
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDifference = today.getMonth() - birthDate.getMonth();
      if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }

      if (age < 18) {
        newErrors.parentBirthDate = 'Ο γονέας/κηδεμόνας πρέπει να είναι άνω των 18 ετών';
      }
    }

    // Consent validation
    if (!formData.consentAccepted) {
      newErrors.consentAccepted = 'Πρέπει να αποδεχτείτε τους όρους';
    }

    // Signature validation
    if (!formData.signature || signaturePadRef.current?.isEmpty()) {
      newErrors.signature = 'Απαιτείται η υπογραφή του γονέα/κηδεμόνα';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (validateForm()) {
      // Get signature data
      if (signaturePadRef.current) {
        const signatureData = signaturePadRef.current.toDataURL();
        formData.signature = signatureData;
      }
      
      onSubmit(formData);
    }
  };

  return (
    <div className="parent-consent-form">
      <h2>Συγκατάθεση Γονέα/Κηδεμόνα</h2>
      <p className="text-warning">
        Επειδή ο αθλούμενος είναι ανήλικος, απαιτείται η συγκατάθεση του γονέα ή κηδεμόνα.
      </p>

      <form onSubmit={handleSubmit}>
        {/* Parent Full Name */}
        <div className="form-group">
          <label>Ονοματεπώνυμο Γονέα/Κηδεμόνα *</label>
          <input
            type="text"
            value={formData.parentFullName}
            onChange={(e) => setFormData({...formData, parentFullName: e.target.value})}
            className={errors.parentFullName ? 'error' : ''}
          />
          {errors.parentFullName && <span className="error-message">{errors.parentFullName}</span>}
        </div>

        {/* Father's Name */}
        <div className="form-row">
          <div className="form-group">
            <label>Όνομα Πατέρα *</label>
            <input
              type="text"
              value={formData.fatherFirstName}
              onChange={(e) => setFormData({...formData, fatherFirstName: e.target.value})}
              className={errors.fatherFirstName ? 'error' : ''}
            />
            {errors.fatherFirstName && <span className="error-message">{errors.fatherFirstName}</span>}
          </div>
          <div className="form-group">
            <label>Επώνυμο Πατέρα *</label>
            <input
              type="text"
              value={formData.fatherLastName}
              onChange={(e) => setFormData({...formData, fatherLastName: e.target.value})}
              className={errors.fatherLastName ? 'error' : ''}
            />
            {errors.fatherLastName && <span className="error-message">{errors.fatherLastName}</span>}
          </div>
        </div>

        {/* Mother's Name */}
        <div className="form-row">
          <div className="form-group">
            <label>Όνομα Μητέρας *</label>
            <input
              type="text"
              value={formData.motherFirstName}
              onChange={(e) => setFormData({...formData, motherFirstName: e.target.value})}
              className={errors.motherFirstName ? 'error' : ''}
            />
            {errors.motherFirstName && <span className="error-message">{errors.motherFirstName}</span>}
          </div>
          <div className="form-group">
            <label>Επώνυμο Μητέρας *</label>
            <input
              type="text"
              value={formData.motherLastName}
              onChange={(e) => setFormData({...formData, motherLastName: e.target.value})}
              className={errors.motherLastName ? 'error' : ''}
            />
            {errors.motherLastName && <span className="error-message">{errors.motherLastName}</span>}
          </div>
        </div>

        {/* Parent Birth Date */}
        <div className="form-group">
          <label>Ημερομηνία Γέννησης Γονέα/Κηδεμόνα *</label>
          <input
            type="date"
            value={formData.parentBirthDate}
            onChange={(e) => setFormData({...formData, parentBirthDate: e.target.value})}
            max={new Date(new Date().setFullYear(new Date().getFullYear() - 18)).toISOString().split('T')[0]}
            className={errors.parentBirthDate ? 'error' : ''}
          />
          {errors.parentBirthDate && <span className="error-message">{errors.parentBirthDate}</span>}
        </div>

        {/* ID Number */}
        <div className="form-group">
          <label>Αριθμός Ταυτότητας *</label>
          <input
            type="text"
            value={formData.parentIdNumber}
            onChange={(e) => setFormData({...formData, parentIdNumber: e.target.value})}
            className={errors.parentIdNumber ? 'error' : ''}
            placeholder="π.χ. ΑΒ123456"
          />
          {errors.parentIdNumber && <span className="error-message">{errors.parentIdNumber}</span>}
        </div>

        {/* Contact Information */}
        <div className="form-row">
          <div className="form-group">
            <label>Τηλέφωνο *</label>
            <input
              type="tel"
              value={formData.parentPhone}
              onChange={(e) => setFormData({...formData, parentPhone: e.target.value})}
              className={errors.parentPhone ? 'error' : ''}
              placeholder="69XXXXXXXX"
            />
            {errors.parentPhone && <span className="error-message">{errors.parentPhone}</span>}
          </div>
          <div className="form-group">
            <label>Email *</label>
            <input
              type="email"
              value={formData.parentEmail}
              onChange={(e) => setFormData({...formData, parentEmail: e.target.value})}
              className={errors.parentEmail ? 'error' : ''}
            />
            {errors.parentEmail && <span className="error-message">{errors.parentEmail}</span>}
          </div>
        </div>

        {/* Address */}
        <div className="form-group">
          <label>Οδός *</label>
          <input
            type="text"
            value={formData.parentStreet}
            onChange={(e) => setFormData({...formData, parentStreet: e.target.value})}
            className={errors.parentStreet ? 'error' : ''}
          />
          {errors.parentStreet && <span className="error-message">{errors.parentStreet}</span>}
        </div>

        <div className="form-row">
          <div className="form-group">
            <label>Αριθμός *</label>
            <input
              type="text"
              value={formData.parentStreetNumber}
              onChange={(e) => setFormData({...formData, parentStreetNumber: e.target.value})}
              className={errors.parentStreetNumber ? 'error' : ''}
            />
            {errors.parentStreetNumber && <span className="error-message">{errors.parentStreetNumber}</span>}
          </div>
          <div className="form-group">
            <label>Ταχ. Κώδικας *</label>
            <input
              type="text"
              value={formData.parentPostalCode}
              onChange={(e) => setFormData({...formData, parentPostalCode: e.target.value})}
              maxLength={5}
              className={errors.parentPostalCode ? 'error' : ''}
            />
            {errors.parentPostalCode && <span className="error-message">{errors.parentPostalCode}</span>}
          </div>
          <div className="form-group">
            <label>Πόλη *</label>
            <input
              type="text"
              value={formData.parentLocation}
              onChange={(e) => setFormData({...formData, parentLocation: e.target.value})}
              className={errors.parentLocation ? 'error' : ''}
            />
            {errors.parentLocation && <span className="error-message">{errors.parentLocation}</span>}
          </div>
        </div>

        {/* Consent Text */}
        <div className="consent-text">
          <h3>Δήλωση Συγκατάθεσης</h3>
          <p>
            Με την παρούσα δηλώνω υπεύθυνα ότι:
          </p>
          <ul>
            <li>Είμαι ο νόμιμος γονέας/κηδεμόνας του ανήλικου αθλούμενου</li>
            <li>Παρέχω τη συγκατάθεσή μου για τη συμμετοχή του στις αθλητικές δραστηριότητες του γυμναστηρίου</li>
            <li>Έχω διαβάσει και αποδέχομαι τους όρους χρήσης και την πολιτική απορρήτου</li>
            <li>Αναλαμβάνω πλήρως την ευθύνη για τυχόν τραυματισμούς ή ζημιές</li>
            <li>Τα στοιχεία που δήλωσα είναι αληθή και ακριβή</li>
          </ul>
        </div>

        {/* Consent Checkbox */}
        <div className="form-group checkbox-group">
          <label>
            <input
              type="checkbox"
              checked={formData.consentAccepted}
              onChange={(e) => setFormData({...formData, consentAccepted: e.target.checked})}
            />
            Αποδέχομαι τους παραπάνω όρους και παρέχω τη συγκατάθεσή μου *
          </label>
          {errors.consentAccepted && <span className="error-message">{errors.consentAccepted}</span>}
        </div>

        {/* Signature Pad */}
        <div className="form-group">
          <label>Υπογραφή Γονέα/Κηδεμόνα *</label>
          <div className="signature-container">
            <SignaturePad
              ref={signaturePadRef}
              canvasProps={{
                className: 'signature-pad',
                width: 500,
                height: 200
              }}
            />
            <button
              type="button"
              onClick={() => signaturePadRef.current?.clear()}
              className="btn-clear-signature"
            >
              Καθαρισμός
            </button>
          </div>
          {errors.signature && <span className="error-message">{errors.signature}</span>}
        </div>

        {/* Form Actions */}
        <div className="form-actions">
          <button type="button" onClick={onBack} className="btn-secondary">
            Πίσω
          </button>
          <button type="submit" className="btn-primary">
            Συνέχεια
          </button>
        </div>
      </form>
    </div>
  );
};
```

### Step 4: Update Main Registration Component

```tsx
// components/Registration.tsx
import React, { useState, useEffect } from 'react';
import { BasicInfoForm } from './BasicInfoForm';
import { ParentConsentForm } from './ParentConsentForm';
import { MedicalHistoryForm } from './MedicalHistoryForm';
import { TermsAndSignature } from './TermsAndSignature';
import { AgeVerificationService } from '../services/ageVerification';

export const Registration: React.FC = () => {
  const [currentStep, setCurrentStep] = useState(1);
  const [isMinor, setIsMinor] = useState(false);
  const [registrationData, setRegistrationData] = useState<RegistrationData>({
    firstName: '',
    lastName: '',
    email: '',
    password: '',
    birthDate: '',
    gender: '',
    phone: '',
    signature: '',
    signedAt: '',
    documentType: 'terms_and_conditions',
    documentVersion: '1.0',
    medicalHistory: {},
    parentConsent: undefined
  });

  const handleBasicInfoSubmit = async (data: BasicInfo) => {
    setRegistrationData(prev => ({ ...prev, ...data }));
    
    // Check age with backend
    try {
      const ageCheck = await AgeVerificationService.checkAge(data.birthDate);
      setIsMinor(ageCheck.isMinor);
      
      if (ageCheck.isMinor) {
        // Show parent consent form
        setCurrentStep(2);
      } else {
        // Skip to medical history
        setCurrentStep(3);
      }
    } catch (error) {
      console.error('Age verification failed:', error);
      alert('Unable to verify age. Please try again.');
    }
  };

  const handleParentConsentSubmit = (consent: ParentConsent) => {
    setRegistrationData(prev => ({ ...prev, parentConsent: consent }));
    setCurrentStep(3); // Move to medical history
  };

  const handleMedicalHistorySubmit = (medicalHistory: any) => {
    setRegistrationData(prev => ({ ...prev, medicalHistory }));
    setCurrentStep(4); // Move to terms and signature
  };

  const handleFinalSubmit = async (signature: string) => {
    const finalData = {
      ...registrationData,
      signature,
      signedAt: new Date().toISOString()
    };

    try {
      const response = await fetch('/api/v1/auth/register-with-consent', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(finalData)
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Registration failed');
      }

      const result = await response.json();
      
      // Success message
      alert(
        isMinor 
          ? 'Η εγγραφή του ανηλίκου υποβλήθηκε επιτυχώς με γονική συγκατάθεση. Περιμένετε την έγκριση από τον διαχειριστή.'
          : 'Η εγγραφή σας υποβλήθηκε επιτυχώς. Περιμένετε την έγκριση από τον διαχειριστή.'
      );
      
      // Redirect to success page or login
      window.location.href = '/registration-success';
      
    } catch (error) {
      console.error('Registration error:', error);
      alert('Registration failed. Please check your information and try again.');
    }
  };

  const renderStep = () => {
    switch (currentStep) {
      case 1:
        return (
          <BasicInfoForm
            data={registrationData}
            onSubmit={handleBasicInfoSubmit}
          />
        );
      
      case 2:
        // Parent consent (only for minors)
        return (
          <ParentConsentForm
            onSubmit={handleParentConsentSubmit}
            onBack={() => setCurrentStep(1)}
          />
        );
      
      case 3:
        return (
          <MedicalHistoryForm
            data={registrationData.medicalHistory}
            onSubmit={handleMedicalHistorySubmit}
            onBack={() => setCurrentStep(isMinor ? 2 : 1)}
          />
        );
      
      case 4:
        return (
          <TermsAndSignature
            isMinor={isMinor}
            onSubmit={handleFinalSubmit}
            onBack={() => setCurrentStep(3)}
          />
        );
      
      default:
        return null;
    }
  };

  return (
    <div className="registration-container">
      {/* Progress Bar */}
      <div className="progress-bar">
        <div className={`step ${currentStep >= 1 ? 'active' : ''}`}>
          <span>1</span>
          <label>Βασικά Στοιχεία</label>
        </div>
        {isMinor && (
          <div className={`step ${currentStep >= 2 ? 'active' : ''}`}>
            <span>2</span>
            <label>Συγκατάθεση Γονέα</label>
          </div>
        )}
        <div className={`step ${currentStep >= 3 ? 'active' : ''}`}>
          <span>{isMinor ? '3' : '2'}</span>
          <label>Ιατρικό Ιστορικό</label>
        </div>
        <div className={`step ${currentStep >= 4 ? 'active' : ''}`}>
          <span>{isMinor ? '4' : '3'}</span>
          <label>Όροι & Υπογραφή</label>
        </div>
      </div>

      {/* Current Step Content */}
      <div className="step-content">
        {renderStep()}
      </div>

      {/* Minor Indicator */}
      {isMinor && (
        <div className="minor-indicator">
          <i className="fas fa-info-circle"></i>
          Εγγραφή Ανηλίκου - Απαιτείται Γονική Συγκατάθεση
        </div>
      )}
    </div>
  );
};
```

### Step 5: CSS Styles for Parent Consent Form

```css
/* styles/parentConsent.css */
.parent-consent-form {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.parent-consent-form h2 {
  color: #333;
  margin-bottom: 10px;
}

.text-warning {
  background-color: #fff3cd;
  border: 1px solid #ffc107;
  border-radius: 4px;
  padding: 10px;
  margin-bottom: 20px;
  color: #856404;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group input[type="date"] {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-group input.error {
  border-color: #dc3545;
}

.error-message {
  color: #dc3545;
  font-size: 12px;
  margin-top: 5px;
  display: block;
}

.form-row {
  display: flex;
  gap: 20px;
}

.form-row .form-group {
  flex: 1;
}

.consent-text {
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 20px;
  margin: 30px 0;
}

.consent-text h3 {
  margin-top: 0;
  color: #333;
}

.consent-text ul {
  margin: 10px 0;
  padding-left: 20px;
}

.consent-text li {
  margin: 5px 0;
}

.checkbox-group {
  background-color: #e7f3ff;
  padding: 15px;
  border-radius: 4px;
  border: 1px solid #b3d9ff;
}

.checkbox-group label {
  display: flex;
  align-items: flex-start;
  cursor: pointer;
}

.checkbox-group input[type="checkbox"] {
  margin-right: 10px;
  margin-top: 3px;
}

.signature-container {
  border: 2px dashed #ddd;
  border-radius: 4px;
  padding: 10px;
  background-color: #fff;
  position: relative;
}

.signature-pad {
  display: block;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.btn-clear-signature {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 5px 10px;
  background-color: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.btn-clear-signature:hover {
  background-color: #5a6268;
}

.form-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid #ddd;
}

.btn-primary,
.btn-secondary {
  padding: 12px 30px;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  cursor: pointer;
  transition: background-color 0.3s;
}

.btn-primary {
  background-color: #007bff;
  color: white;
}

.btn-primary:hover {
  background-color: #0056b3;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background-color: #5a6268;
}

.minor-indicator {
  background-color: #fff3cd;
  border: 1px solid #ffc107;
  border-radius: 4px;
  padding: 10px;
  text-align: center;
  margin-top: 20px;
  color: #856404;
  font-weight: 500;
}

.minor-indicator i {
  margin-right: 5px;
}

/* Progress Bar Styles */
.progress-bar {
  display: flex;
  justify-content: center;
  margin-bottom: 40px;
}

.progress-bar .step {
  position: relative;
  flex: 1;
  text-align: center;
  color: #999;
}

.progress-bar .step:not(:last-child)::after {
  content: '';
  position: absolute;
  top: 20px;
  left: 50%;
  width: 100%;
  height: 2px;
  background-color: #ddd;
  z-index: -1;
}

.progress-bar .step.active {
  color: #007bff;
}

.progress-bar .step.active::after {
  background-color: #007bff;
}

.progress-bar .step span {
  display: inline-block;
  width: 40px;
  height: 40px;
  line-height: 40px;
  background-color: #fff;
  border: 2px solid #ddd;
  border-radius: 50%;
  margin-bottom: 5px;
}

.progress-bar .step.active span {
  background-color: #007bff;
  color: white;
  border-color: #007bff;
}

.progress-bar .step label {
  display: block;
  font-size: 12px;
}
```

## Validation Rules Summary

### Parent Consent Validation (Backend enforced):
1. **Parent Age**: Must be 18+ years old
2. **Parent ID Number**: Must be unique (no duplicate registrations)
3. **Postal Code**: Must be exactly 5 digits
4. **All fields are required** when registering a minor
5. **Signature**: Must be valid base64 image data

### Error Handling

```typescript
// Handle specific validation errors
const handleRegistrationError = (error: any) => {
  if (error.errors) {
    // Field-specific errors
    if (error.errors['parentConsent.parentIdNumber']) {
      alert('Αυτός ο αριθμός ταυτότητας έχει ήδη χρησιμοποιηθεί για άλλη εγγραφή.');
    }
    if (error.errors['parentConsent.parentBirthDate']) {
      alert('Ο γονέας/κηδεμόνας πρέπει να είναι τουλάχιστον 18 ετών.');
    }
    // ... handle other errors
  } else {
    alert(error.message || 'Registration failed');
  }
};
```

## Testing Checklist

- [ ] Test adult registration (no parent consent required)
- [ ] Test minor registration with parent consent
- [ ] Test age boundary cases (turning 18 today, yesterday, tomorrow)
- [ ] Test parent ID uniqueness validation
- [ ] Test parent age validation (must be 18+)
- [ ] Test postal code format validation
- [ ] Test signature capture and submission
- [ ] Test form navigation (back/forward)
- [ ] Test error display for all fields
- [ ] Test successful registration message

## Important Notes

1. **NEVER calculate age on the client** - Always use the `/api/v1/auth/check-age` endpoint
2. **Parent consent is REQUIRED** for anyone under 18
3. **Parent must be 18+ years old** - This is validated server-side
4. **Parent ID number must be unique** - Prevents duplicate registrations
5. **All timestamps are server-generated** for legal validity

## Support

If you encounter any issues or need clarification:
- Check the backend logs for detailed error messages
- Ensure all required fields are being sent
- Verify date formats are YYYY-MM-DD
- Check that signature data is valid base64 image format

---

**Last Updated**: August 6, 2025
**Backend Status**: ✅ Ready for integration
**API Version**: 1.0