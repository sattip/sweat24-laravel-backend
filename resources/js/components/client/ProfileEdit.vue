<template>
  <div class="profile-edit-container">
    <div class="container-fluid">
      <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3">
          <div class="profile-sidebar">
            <div class="profile-avatar-section text-center mb-4">
              <img 
                :src="avatarUrl || '/images/default-avatar.png'" 
                alt="Profile" 
                class="profile-avatar mb-3"
              >
              <input 
                type="file" 
                ref="avatarInput" 
                @change="handleAvatarChange" 
                accept="image/*" 
                class="d-none"
              >
              <button 
                @click="$refs.avatarInput.click()" 
                class="btn btn-sm btn-outline-primary"
              >
                Change Photo
              </button>
            </div>

            <nav class="profile-nav">
              <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                  <a 
                    class="nav-link" 
                    :class="{ active: activeTab === 'personal' }"
                    @click="activeTab = 'personal'"
                    href="#"
                  >
                    <i class="fas fa-user me-2"></i> Personal Info
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link" 
                    :class="{ active: activeTab === 'password' }"
                    @click="activeTab = 'password'"
                    href="#"
                  >
                    <i class="fas fa-lock me-2"></i> Password
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link" 
                    :class="{ active: activeTab === 'emergency' }"
                    @click="activeTab = 'emergency'"
                    href="#"
                  >
                    <i class="fas fa-phone-alt me-2"></i> Emergency Contact
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link" 
                    :class="{ active: activeTab === 'medical' }"
                    @click="activeTab = 'medical'"
                    href="#"
                  >
                    <i class="fas fa-notes-medical me-2"></i> Medical Info
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link" 
                    :class="{ active: activeTab === 'notifications' }"
                    @click="activeTab = 'notifications'"
                    href="#"
                  >
                    <i class="fas fa-bell me-2"></i> Notifications
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link" 
                    :class="{ active: activeTab === 'privacy' }"
                    @click="activeTab = 'privacy'"
                    href="#"
                  >
                    <i class="fas fa-shield-alt me-2"></i> Privacy
                  </a>
                </li>
              </ul>
            </nav>
          </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
          <div class="profile-content">
            <!-- Personal Information -->
            <div v-if="activeTab === 'personal'" class="tab-content">
              <h3 class="mb-4">Personal Information</h3>
              <form @submit.prevent="updateProfile">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      id="name"
                      v-model="profile.name"
                      :class="{ 'is-invalid': errors.name }"
                    >
                    <div v-if="errors.name" class="invalid-feedback">
                      {{ errors.name[0] }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input 
                      type="email" 
                      class="form-control" 
                      id="email"
                      v-model="profile.email"
                      :class="{ 'is-invalid': errors.email }"
                    >
                    <div v-if="errors.email" class="invalid-feedback">
                      {{ errors.email[0] }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input 
                      type="tel" 
                      class="form-control" 
                      id="phone"
                      v-model="profile.phone"
                      :class="{ 'is-invalid': errors.phone }"
                    >
                    <div v-if="errors.phone" class="invalid-feedback">
                      {{ errors.phone[0] }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                    <input 
                      type="date" 
                      class="form-control" 
                      id="date_of_birth"
                      v-model="profile.date_of_birth"
                      :class="{ 'is-invalid': errors.date_of_birth }"
                    >
                    <div v-if="errors.date_of_birth" class="invalid-feedback">
                      {{ errors.date_of_birth[0] }}
                    </div>
                  </div>

                  <div class="col-12 mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea 
                      class="form-control" 
                      id="address"
                      v-model="profile.address"
                      rows="2"
                      :class="{ 'is-invalid': errors.address }"
                    ></textarea>
                    <div v-if="errors.address" class="invalid-feedback">
                      {{ errors.address[0] }}
                    </div>
                  </div>

                  <div class="col-12">
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                      <span v-if="saving">
                        <i class="fas fa-spinner fa-spin me-1"></i> Saving...
                      </span>
                      <span v-else>
                        <i class="fas fa-save me-1"></i> Save Changes
                      </span>
                    </button>
                  </div>
                </div>
              </form>
            </div>

            <!-- Password Change -->
            <div v-if="activeTab === 'password'" class="tab-content">
              <h3 class="mb-4">Change Password</h3>
              <form @submit.prevent="updatePassword">
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="current_password" class="form-label">Current Password</label>
                      <input 
                        type="password" 
                        class="form-control" 
                        id="current_password"
                        v-model="passwordForm.current_password"
                        :class="{ 'is-invalid': errors.current_password }"
                      >
                      <div v-if="errors.current_password" class="invalid-feedback">
                        {{ errors.current_password[0] }}
                      </div>
                    </div>

                    <div class="mb-3">
                      <label for="password" class="form-label">New Password</label>
                      <input 
                        type="password" 
                        class="form-control" 
                        id="password"
                        v-model="passwordForm.password"
                        :class="{ 'is-invalid': errors.password }"
                      >
                      <div v-if="errors.password" class="invalid-feedback">
                        {{ errors.password[0] }}
                      </div>
                    </div>

                    <div class="mb-3">
                      <label for="password_confirmation" class="form-label">Confirm New Password</label>
                      <input 
                        type="password" 
                        class="form-control" 
                        id="password_confirmation"
                        v-model="passwordForm.password_confirmation"
                      >
                    </div>

                    <button type="submit" class="btn btn-primary" :disabled="saving">
                      <span v-if="saving">
                        <i class="fas fa-spinner fa-spin me-1"></i> Updating...
                      </span>
                      <span v-else>
                        <i class="fas fa-lock me-1"></i> Update Password
                      </span>
                    </button>
                  </div>
                  <div class="col-md-6">
                    <div class="alert alert-info">
                      <h6 class="alert-heading">Password Requirements:</h6>
                      <ul class="mb-0">
                        <li>At least 8 characters long</li>
                        <li>Include uppercase and lowercase letters</li>
                        <li>Include at least one number</li>
                        <li>Include at least one special character</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </form>
            </div>

            <!-- Emergency Contact -->
            <div v-if="activeTab === 'emergency'" class="tab-content">
              <h3 class="mb-4">Emergency Contact</h3>
              <form @submit.prevent="updateProfile">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="emergency_contact" class="form-label">Contact Name</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      id="emergency_contact"
                      v-model="profile.emergency_contact"
                      :class="{ 'is-invalid': errors.emergency_contact }"
                    >
                    <div v-if="errors.emergency_contact" class="invalid-feedback">
                      {{ errors.emergency_contact[0] }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="emergency_phone" class="form-label">Contact Phone</label>
                    <input 
                      type="tel" 
                      class="form-control" 
                      id="emergency_phone"
                      v-model="profile.emergency_phone"
                      :class="{ 'is-invalid': errors.emergency_phone }"
                    >
                    <div v-if="errors.emergency_phone" class="invalid-feedback">
                      {{ errors.emergency_phone[0] }}
                    </div>
                  </div>

                  <div class="col-12">
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                      <span v-if="saving">
                        <i class="fas fa-spinner fa-spin me-1"></i> Saving...
                      </span>
                      <span v-else>
                        <i class="fas fa-save me-1"></i> Save Emergency Contact
                      </span>
                    </button>
                  </div>
                </div>
              </form>
            </div>

            <!-- Medical History -->
            <div v-if="activeTab === 'medical'" class="tab-content">
              <h3 class="mb-4">Medical Information</h3>
              <form @submit.prevent="updateProfile">
                <div class="mb-3">
                  <label for="medical_history" class="form-label">Medical History / Conditions</label>
                  <textarea 
                    class="form-control" 
                    id="medical_history"
                    v-model="profile.medical_history"
                    rows="5"
                    :class="{ 'is-invalid': errors.medical_history }"
                    placeholder="Please list any medical conditions, allergies, or injuries that may affect your training..."
                  ></textarea>
                  <div v-if="errors.medical_history" class="invalid-feedback">
                    {{ errors.medical_history[0] }}
                  </div>
                </div>

                <div class="mb-3">
                  <label for="notes" class="form-label">Additional Notes</label>
                  <textarea 
                    class="form-control" 
                    id="notes"
                    v-model="profile.notes"
                    rows="3"
                    :class="{ 'is-invalid': errors.notes }"
                    placeholder="Any other information you'd like to share with your trainers..."
                  ></textarea>
                  <div v-if="errors.notes" class="invalid-feedback">
                    {{ errors.notes[0] }}
                  </div>
                </div>

                <button type="submit" class="btn btn-primary" :disabled="saving">
                  <span v-if="saving">
                    <i class="fas fa-spinner fa-spin me-1"></i> Saving...
                  </span>
                  <span v-else>
                    <i class="fas fa-save me-1"></i> Save Medical Info
                  </span>
                </button>
              </form>
            </div>

            <!-- Notification Preferences -->
            <div v-if="activeTab === 'notifications'" class="tab-content">
              <h3 class="mb-4">Notification Preferences</h3>
              <form @submit.prevent="updateNotificationPreferences">
                <div class="notification-settings">
                  <div class="form-check form-switch mb-3">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="email_notifications"
                      v-model="notificationPreferences.email_notifications"
                    >
                    <label class="form-check-label" for="email_notifications">
                      Email Notifications
                      <small class="text-muted d-block">Receive updates via email</small>
                    </label>
                  </div>

                  <div class="form-check form-switch mb-3">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="sms_notifications"
                      v-model="notificationPreferences.sms_notifications"
                    >
                    <label class="form-check-label" for="sms_notifications">
                      SMS Notifications
                      <small class="text-muted d-block">Receive text messages for important updates</small>
                    </label>
                  </div>

                  <div class="form-check form-switch mb-3">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="push_notifications"
                      v-model="notificationPreferences.push_notifications"
                    >
                    <label class="form-check-label" for="push_notifications">
                      Push Notifications
                      <small class="text-muted d-block">Receive notifications in the app</small>
                    </label>
                  </div>

                  <hr class="my-4">

                  <h5 class="mb-3">Notification Types</h5>

                  <div class="form-check form-switch mb-3">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="booking_reminders"
                      v-model="notificationPreferences.booking_reminders"
                    >
                    <label class="form-check-label" for="booking_reminders">
                      Booking Reminders
                      <small class="text-muted d-block">Get reminded about upcoming classes</small>
                    </label>
                  </div>

                  <div class="form-check form-switch mb-3">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="package_expiry_alerts"
                      v-model="notificationPreferences.package_expiry_alerts"
                    >
                    <label class="form-check-label" for="package_expiry_alerts">
                      Package Expiry Alerts
                      <small class="text-muted d-block">Be notified when your package is about to expire</small>
                    </label>
                  </div>

                  <div class="form-check form-switch mb-3">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="promotional_emails"
                      v-model="notificationPreferences.promotional_emails"
                    >
                    <label class="form-check-label" for="promotional_emails">
                      Promotional Emails
                      <small class="text-muted d-block">Receive news about special offers and events</small>
                    </label>
                  </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3" :disabled="saving">
                  <span v-if="saving">
                    <i class="fas fa-spinner fa-spin me-1"></i> Saving...
                  </span>
                  <span v-else>
                    <i class="fas fa-save me-1"></i> Save Preferences
                  </span>
                </button>
              </form>
            </div>

            <!-- Privacy Settings -->
            <div v-if="activeTab === 'privacy'" class="tab-content">
              <h3 class="mb-4">Privacy Settings</h3>
              <form @submit.prevent="updatePrivacySettings">
                <div class="privacy-settings">
                  <div class="form-check form-switch mb-3">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="show_profile_to_trainers"
                      v-model="privacySettings.show_profile_to_trainers"
                    >
                    <label class="form-check-label" for="show_profile_to_trainers">
                      Show Profile to Trainers
                      <small class="text-muted d-block">Allow trainers to view your profile information</small>
                    </label>
                  </div>

                  <div class="form-check form-switch mb-3">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="show_attendance_history"
                      v-model="privacySettings.show_attendance_history"
                    >
                    <label class="form-check-label" for="show_attendance_history">
                      Share Attendance History
                      <small class="text-muted d-block">Allow trainers to see your class attendance history</small>
                    </label>
                  </div>

                  <div class="form-check form-switch mb-3">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="allow_photo_in_gym"
                      v-model="privacySettings.allow_photo_in_gym"
                    >
                    <label class="form-check-label" for="allow_photo_in_gym">
                      Allow Photos in Gym
                      <small class="text-muted d-block">You may appear in gym promotional photos</small>
                    </label>
                  </div>

                  <div class="form-check form-switch mb-3">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="share_progress_reports"
                      v-model="privacySettings.share_progress_reports"
                    >
                    <label class="form-check-label" for="share_progress_reports">
                      Share Progress Reports
                      <small class="text-muted d-block">Allow trainers to create and share progress reports</small>
                    </label>
                  </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3" :disabled="saving">
                  <span v-if="saving">
                    <i class="fas fa-spinner fa-spin me-1"></i> Saving...
                  </span>
                  <span v-else>
                    <i class="fas fa-save me-1"></i> Save Privacy Settings
                  </span>
                </button>
              </form>

              <hr class="my-5">

              <div class="account-actions">
                <h5 class="mb-3">Account Actions</h5>
                <button @click="showDeactivationModal = true" class="btn btn-danger">
                  <i class="fas fa-user-times me-1"></i> Request Account Deactivation
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <div v-if="successMessage" class="toast-container position-fixed bottom-0 end-0 p-3">
      <div class="toast show" role="alert">
        <div class="toast-header bg-success text-white">
          <strong class="me-auto">Success</strong>
          <button type="button" class="btn-close btn-close-white" @click="successMessage = ''"></button>
        </div>
        <div class="toast-body">
          {{ successMessage }}
        </div>
      </div>
    </div>

    <!-- Deactivation Modal -->
    <div v-if="showDeactivationModal" class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Request Account Deactivation</h5>
            <button type="button" class="btn-close" @click="showDeactivationModal = false"></button>
          </div>
          <form @submit.prevent="requestDeactivation">
            <div class="modal-body">
              <p>We're sorry to see you go. Please tell us why you're leaving:</p>
              
              <div class="mb-3">
                <label for="deactivation_reason" class="form-label">Reason for leaving *</label>
                <select 
                  class="form-select" 
                  id="deactivation_reason"
                  v-model="deactivationForm.reason"
                  required
                >
                  <option value="">Select a reason</option>
                  <option value="moving">Moving to another location</option>
                  <option value="financial">Financial reasons</option>
                  <option value="health">Health reasons</option>
                  <option value="dissatisfied">Dissatisfied with service</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="deactivation_feedback" class="form-label">Additional feedback</label>
                <textarea 
                  class="form-control" 
                  id="deactivation_feedback"
                  v-model="deactivationForm.feedback"
                  rows="3"
                  placeholder="Please share any additional feedback..."
                ></textarea>
              </div>

              <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-1"></i>
                Your account will remain active until an administrator processes your request. 
                You will be contacted within 48 hours.
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showDeactivationModal = false">
                Cancel
              </button>
              <button type="submit" class="btn btn-danger" :disabled="saving">
                Submit Request
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ProfileEdit',
  
  data() {
    return {
      activeTab: 'personal',
      profile: {
        name: '',
        email: '',
        phone: '',
        address: '',
        date_of_birth: '',
        emergency_contact: '',
        emergency_phone: '',
        medical_history: '',
        notes: ''
      },
      passwordForm: {
        current_password: '',
        password: '',
        password_confirmation: ''
      },
      notificationPreferences: {
        email_notifications: true,
        sms_notifications: true,
        push_notifications: true,
        booking_reminders: true,
        package_expiry_alerts: true,
        promotional_emails: false
      },
      privacySettings: {
        show_profile_to_trainers: true,
        show_attendance_history: true,
        allow_photo_in_gym: true,
        share_progress_reports: true
      },
      deactivationForm: {
        reason: '',
        feedback: ''
      },
      avatarUrl: null,
      errors: {},
      saving: false,
      successMessage: '',
      showDeactivationModal: false
    };
  },
  
  mounted() {
    this.loadProfile();
    this.loadNotificationPreferences();
    this.loadPrivacySettings();
  },
  
  methods: {
    async loadProfile() {
      try {
        const response = await axios.get('/api/v1/profile');
        const user = response.data.user;
        
        this.profile = {
          name: user.name || '',
          email: user.email || '',
          phone: user.phone || '',
          address: user.address || '',
          date_of_birth: user.date_of_birth || '',
          emergency_contact: user.emergency_contact || '',
          emergency_phone: user.emergency_phone || '',
          medical_history: user.medical_history || '',
          notes: user.notes || ''
        };
        
        if (user.avatar) {
          this.avatarUrl = `/storage/${user.avatar}`;
        }
      } catch (error) {
        console.error('Error loading profile:', error);
      }
    },
    
    async loadNotificationPreferences() {
      try {
        const response = await axios.get('/api/v1/profile/notification-preferences');
        this.notificationPreferences = response.data;
      } catch (error) {
        console.error('Error loading notification preferences:', error);
      }
    },
    
    async loadPrivacySettings() {
      try {
        const response = await axios.get('/api/v1/profile/privacy-settings');
        this.privacySettings = response.data;
      } catch (error) {
        console.error('Error loading privacy settings:', error);
      }
    },
    
    async updateProfile() {
      this.saving = true;
      this.errors = {};
      
      try {
        const response = await axios.put('/api/v1/profile', this.profile);
        this.successMessage = response.data.message;
        this.clearSuccessMessage();
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors;
        } else {
          console.error('Error updating profile:', error);
        }
      } finally {
        this.saving = false;
      }
    },
    
    async updatePassword() {
      this.saving = true;
      this.errors = {};
      
      try {
        const response = await axios.put('/api/v1/profile/password', this.passwordForm);
        this.successMessage = response.data.message;
        this.passwordForm = {
          current_password: '',
          password: '',
          password_confirmation: ''
        };
        this.clearSuccessMessage();
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors;
        } else {
          console.error('Error updating password:', error);
        }
      } finally {
        this.saving = false;
      }
    },
    
    async handleAvatarChange(event) {
      const file = event.target.files[0];
      if (!file) return;
      
      const formData = new FormData();
      formData.append('avatar', file);
      
      this.saving = true;
      
      try {
        const response = await axios.post('/api/v1/profile/avatar', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        
        this.avatarUrl = response.data.avatar_url;
        this.successMessage = response.data.message;
        this.clearSuccessMessage();
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors;
        } else {
          console.error('Error uploading avatar:', error);
        }
      } finally {
        this.saving = false;
      }
    },
    
    async updateNotificationPreferences() {
      this.saving = true;
      
      try {
        const response = await axios.put('/api/v1/profile/notification-preferences', this.notificationPreferences);
        this.successMessage = response.data.message;
        this.clearSuccessMessage();
      } catch (error) {
        console.error('Error updating notification preferences:', error);
      } finally {
        this.saving = false;
      }
    },
    
    async updatePrivacySettings() {
      this.saving = true;
      
      try {
        const response = await axios.put('/api/v1/profile/privacy-settings', this.privacySettings);
        this.successMessage = response.data.message;
        this.clearSuccessMessage();
      } catch (error) {
        console.error('Error updating privacy settings:', error);
      } finally {
        this.saving = false;
      }
    },
    
    async requestDeactivation() {
      if (!this.deactivationForm.reason) {
        return;
      }
      
      this.saving = true;
      
      try {
        const response = await axios.post('/api/v1/profile/deactivation-request', this.deactivationForm);
        this.successMessage = response.data.message;
        this.showDeactivationModal = false;
        this.deactivationForm = { reason: '', feedback: '' };
        this.clearSuccessMessage();
      } catch (error) {
        console.error('Error requesting deactivation:', error);
      } finally {
        this.saving = false;
      }
    },
    
    clearSuccessMessage() {
      setTimeout(() => {
        this.successMessage = '';
      }, 5000);
    }
  }
};
</script>

<style scoped>
.profile-edit-container {
  min-height: 100vh;
  background-color: #f8f9fa;
  padding: 2rem 0;
}

.profile-sidebar {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.profile-avatar {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid #e9ecef;
}

.profile-nav .nav-link {
  color: #495057;
  border-radius: 5px;
  margin-bottom: 0.5rem;
  transition: all 0.3s;
}

.profile-nav .nav-link:hover {
  background-color: #f8f9fa;
}

.profile-nav .nav-link.active {
  background-color: #007bff;
  color: white;
}

.profile-content {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.tab-content {
  display: block;
}

.form-check-input {
  cursor: pointer;
}

.form-check-label {
  cursor: pointer;
  user-select: none;
}

.notification-settings,
.privacy-settings {
  max-width: 600px;
}

.toast-container {
  z-index: 1050;
}

.modal {
  z-index: 1050;
}

@media (max-width: 768px) {
  .profile-sidebar {
    margin-bottom: 2rem;
  }
  
  .profile-nav .nav-link {
    font-size: 0.9rem;
    padding: 0.5rem;
  }
  
  .profile-avatar {
    width: 100px;
    height: 100px;
  }
}
</style>