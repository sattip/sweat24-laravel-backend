<template>
  <div class="booking-management">
    <div class="container-fluid">
      <h2 class="mb-4">My Bookings</h2>
      
      <!-- Booking Tabs -->
      <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
          <a 
            class="nav-link" 
            :class="{ active: activeTab === 'upcoming' }"
            @click="activeTab = 'upcoming'"
            href="#"
          >
            Upcoming Bookings
          </a>
        </li>
        <li class="nav-item">
          <a 
            class="nav-link" 
            :class="{ active: activeTab === 'past' }"
            @click="activeTab = 'past'"
            href="#"
          >
            Past Bookings
          </a>
        </li>
      </ul>
      
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      
      <!-- Upcoming Bookings -->
      <div v-else-if="activeTab === 'upcoming'" class="bookings-list">
        <div v-if="upcomingBookings.length === 0" class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          You don't have any upcoming bookings.
        </div>
        
        <div v-else class="row">
          <div v-for="booking in upcomingBookings" :key="booking.id" class="col-lg-6 mb-4">
            <div class="booking-card">
              <div class="booking-header">
                <h5 class="mb-1">{{ booking.gymClass.name }}</h5>
                <span class="badge" :class="getStatusBadgeClass(booking.status)">
                  {{ booking.status }}
                </span>
              </div>
              
              <div class="booking-body">
                <div class="booking-info mb-2">
                  <i class="fas fa-calendar me-2"></i>
                  {{ formatDate(booking.scheduled_at) }}
                </div>
                <div class="booking-info mb-2">
                  <i class="fas fa-clock me-2"></i>
                  {{ formatTime(booking.scheduled_at) }} - {{ calculateEndTime(booking.scheduled_at, booking.gymClass.duration) }}
                </div>
                <div class="booking-info mb-2">
                  <i class="fas fa-user me-2"></i>
                  Instructor: {{ booking.gymClass.instructor.name }}
                </div>
                <div class="booking-info mb-3">
                  <i class="fas fa-map-marker-alt me-2"></i>
                  {{ booking.gymClass.location || 'Main Gym' }}
                </div>
                
                <!-- Booking Notes -->
                <div v-if="booking.notes || editingNotes === booking.id" class="mb-3">
                  <label class="form-label small text-muted">Notes:</label>
                  <div v-if="editingNotes === booking.id">
                    <textarea 
                      v-model="tempNotes"
                      class="form-control form-control-sm mb-2"
                      rows="2"
                      placeholder="Add notes for this booking..."
                    ></textarea>
                    <button 
                      @click="saveNotes(booking.id)" 
                      class="btn btn-sm btn-success me-1"
                      :disabled="savingNotes"
                    >
                      Save
                    </button>
                    <button 
                      @click="cancelEditNotes()" 
                      class="btn btn-sm btn-secondary"
                    >
                      Cancel
                    </button>
                  </div>
                  <div v-else>
                    <p class="small mb-1">{{ booking.notes }}</p>
                    <button 
                      @click="startEditNotes(booking)" 
                      class="btn btn-sm btn-link p-0"
                    >
                      <i class="fas fa-edit me-1"></i> Edit
                    </button>
                  </div>
                </div>
                
                <div v-else>
                  <button 
                    @click="startEditNotes(booking)" 
                    class="btn btn-sm btn-outline-secondary"
                  >
                    <i class="fas fa-plus me-1"></i> Add Notes
                  </button>
                </div>
              </div>
              
              <div class="booking-footer">
                <button 
                  v-if="canCancel(booking)"
                  @click="showCancelModal(booking)" 
                  class="btn btn-sm btn-danger"
                >
                  <i class="fas fa-times me-1"></i> Cancel Booking
                </button>
                <button 
                  v-if="canReschedule(booking)"
                  @click="showRescheduleModal(booking)" 
                  class="btn btn-sm btn-warning"
                >
                  <i class="fas fa-calendar-alt me-1"></i> Reschedule
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Past Bookings -->
      <div v-else-if="activeTab === 'past'" class="bookings-list">
        <div v-if="pastBookings.length === 0" class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          You don't have any past bookings.
        </div>
        
        <div v-else class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Date</th>
                <th>Class</th>
                <th>Instructor</th>
                <th>Status</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="booking in pastBookings" :key="booking.id">
                <td>{{ formatDate(booking.scheduled_at) }}</td>
                <td>{{ booking.gymClass.name }}</td>
                <td>{{ booking.gymClass.instructor.name }}</td>
                <td>
                  <span class="badge" :class="getStatusBadgeClass(booking.status)">
                    {{ booking.status }}
                  </span>
                </td>
                <td>
                  <small>{{ booking.notes || '-' }}</small>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Cancel Modal -->
    <div v-if="cancelModalBooking" class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Cancel Booking</h5>
            <button type="button" class="btn-close" @click="cancelModalBooking = null"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to cancel this booking?</p>
            <div class="alert alert-warning">
              <strong>{{ cancelModalBooking.gymClass.name }}</strong><br>
              {{ formatDate(cancelModalBooking.scheduled_at) }} at {{ formatTime(cancelModalBooking.scheduled_at) }}
            </div>
            <div v-if="cancellationPolicy" class="alert alert-info">
              <small>{{ cancellationPolicy }}</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cancelModalBooking = null">
              Close
            </button>
            <button 
              type="button" 
              class="btn btn-danger" 
              @click="confirmCancel()"
              :disabled="cancelling"
            >
              <span v-if="cancelling">
                <i class="fas fa-spinner fa-spin me-1"></i> Cancelling...
              </span>
              <span v-else>
                Confirm Cancel
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'BookingManagement',
  
  data() {
    return {
      activeTab: 'upcoming',
      bookings: [],
      loading: false,
      editingNotes: null,
      tempNotes: '',
      savingNotes: false,
      cancelModalBooking: null,
      cancelling: false,
      cancellationPolicy: null
    };
  },
  
  computed: {
    upcomingBookings() {
      const now = new Date();
      return this.bookings.filter(booking => 
        new Date(booking.scheduled_at) >= now && 
        ['confirmed', 'pending'].includes(booking.status)
      );
    },
    
    pastBookings() {
      const now = new Date();
      return this.bookings.filter(booking => 
        new Date(booking.scheduled_at) < now || 
        ['completed', 'cancelled', 'no_show'].includes(booking.status)
      );
    }
  },
  
  mounted() {
    this.loadBookings();
  },
  
  methods: {
    async loadBookings() {
      this.loading = true;
      try {
        const response = await axios.get('/api/v1/profile/booking-history');
        this.bookings = response.data.data;
      } catch (error) {
        console.error('Error loading bookings:', error);
      } finally {
        this.loading = false;
      }
    },
    
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
    },
    
    formatTime(dateString) {
      const date = new Date(dateString);
      return date.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
      });
    },
    
    calculateEndTime(startTime, duration) {
      const start = new Date(startTime);
      const end = new Date(start.getTime() + duration * 60000);
      return end.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
      });
    },
    
    getStatusBadgeClass(status) {
      const classes = {
        'confirmed': 'bg-success',
        'pending': 'bg-warning',
        'completed': 'bg-info',
        'cancelled': 'bg-danger',
        'no_show': 'bg-secondary'
      };
      return classes[status] || 'bg-secondary';
    },
    
    canCancel(booking) {
      const now = new Date();
      const bookingTime = new Date(booking.scheduled_at);
      const hoursUntilClass = (bookingTime - now) / (1000 * 60 * 60);
      
      return booking.status === 'confirmed' && hoursUntilClass > 2; // 2 hour cancellation policy
    },
    
    canReschedule(booking) {
      const now = new Date();
      const bookingTime = new Date(booking.scheduled_at);
      const hoursUntilClass = (bookingTime - now) / (1000 * 60 * 60);
      
      return booking.status === 'confirmed' && hoursUntilClass > 24; // 24 hour reschedule policy
    },
    
    startEditNotes(booking) {
      this.editingNotes = booking.id;
      this.tempNotes = booking.notes || '';
    },
    
    cancelEditNotes() {
      this.editingNotes = null;
      this.tempNotes = '';
    },
    
    async saveNotes(bookingId) {
      this.savingNotes = true;
      try {
        const response = await axios.put(`/api/v1/profile/bookings/${bookingId}/notes`, {
          notes: this.tempNotes
        });
        
        // Update the booking in the list
        const booking = this.bookings.find(b => b.id === bookingId);
        if (booking) {
          booking.notes = this.tempNotes;
        }
        
        this.editingNotes = null;
        this.tempNotes = '';
      } catch (error) {
        console.error('Error saving notes:', error);
      } finally {
        this.savingNotes = false;
      }
    },
    
    async showCancelModal(booking) {
      this.cancelModalBooking = booking;
      
      // Check cancellation policy
      try {
        const response = await axios.get(`/api/v1/bookings/${booking.id}/policy-check`);
        this.cancellationPolicy = response.data.message;
      } catch (error) {
        console.error('Error checking policy:', error);
      }
    },
    
    async confirmCancel() {
      if (!this.cancelModalBooking) return;
      
      this.cancelling = true;
      try {
        await axios.post(`/api/v1/bookings/${this.cancelModalBooking.id}/cancel`);
        
        // Update the booking status
        const booking = this.bookings.find(b => b.id === this.cancelModalBooking.id);
        if (booking) {
          booking.status = 'cancelled';
        }
        
        this.cancelModalBooking = null;
      } catch (error) {
        console.error('Error cancelling booking:', error);
        alert('Failed to cancel booking. Please try again.');
      } finally {
        this.cancelling = false;
      }
    },
    
    showRescheduleModal(booking) {
      // Implement reschedule functionality
      alert('Reschedule functionality coming soon!');
    }
  }
};
</script>

<style scoped>
.booking-management {
  padding: 2rem 0;
}

.booking-card {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 10px;
  padding: 1.5rem;
  height: 100%;
  transition: all 0.3s;
}

.booking-card:hover {
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.booking-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e9ecef;
}

.booking-info {
  color: #6c757d;
  font-size: 0.9rem;
}

.booking-info i {
  width: 20px;
  color: #007bff;
}

.booking-footer {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
  display: flex;
  gap: 0.5rem;
}

.nav-tabs .nav-link {
  color: #6c757d;
  border: none;
  border-bottom: 2px solid transparent;
}

.nav-tabs .nav-link:hover {
  border-bottom-color: #dee2e6;
}

.nav-tabs .nav-link.active {
  color: #007bff;
  border-bottom-color: #007bff;
}

@media (max-width: 768px) {
  .booking-card {
    margin-bottom: 1rem;
  }
  
  .booking-footer {
    flex-direction: column;
  }
  
  .booking-footer button {
    width: 100%;
  }
}
</style>