<template>
  <div class="client-dashboard">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
      <div class="container-fluid">
        <a class="navbar-brand" href="#">
          <i class="fas fa-dumbbell me-2"></i> SWEAT24
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav me-auto">
            <li class="nav-item">
              <a 
                class="nav-link" 
                :class="{ active: currentView === 'dashboard' }"
                @click="currentView = 'dashboard'"
                href="#"
              >
                <i class="fas fa-home me-1"></i> Dashboard
              </a>
            </li>
            <li class="nav-item">
              <a 
                class="nav-link" 
                :class="{ active: currentView === 'bookings' }"
                @click="currentView = 'bookings'"
                href="#"
              >
                <i class="fas fa-calendar me-1"></i> My Bookings
              </a>
            </li>
            <li class="nav-item">
              <a 
                class="nav-link" 
                :class="{ active: currentView === 'profile' }"
                @click="currentView = 'profile'"
                href="#"
              >
                <i class="fas fa-user-edit me-1"></i> Edit Profile
              </a>
            </li>
          </ul>
          
          <ul class="navbar-nav">
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                <i class="fas fa-user-circle me-1"></i> {{ userName }}
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <a class="dropdown-item" @click="currentView = 'profile'" href="#">
                    <i class="fas fa-cog me-2"></i> Settings
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a class="dropdown-item" @click="logout" href="#">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    
    <!-- Main Content -->
    <div class="main-content">
      <!-- Dashboard View -->
      <div v-if="currentView === 'dashboard'" class="container-fluid py-4">
        <h2 class="mb-4">Welcome back, {{ userName }}!</h2>
        
        <!-- Quick Stats -->
        <div class="row mb-4">
          <div class="col-md-3 mb-3">
            <div class="stat-card bg-primary text-white">
              <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
              </div>
              <div class="stat-content">
                <h3>{{ stats.upcomingBookings }}</h3>
                <p>Upcoming Classes</p>
              </div>
            </div>
          </div>
          
          <div class="col-md-3 mb-3">
            <div class="stat-card bg-success text-white">
              <div class="stat-icon">
                <i class="fas fa-fire"></i>
              </div>
              <div class="stat-content">
                <h3>{{ stats.completedSessions }}</h3>
                <p>Completed Sessions</p>
              </div>
            </div>
          </div>
          
          <div class="col-md-3 mb-3">
            <div class="stat-card bg-warning text-white">
              <div class="stat-icon">
                <i class="fas fa-ticket-alt"></i>
              </div>
              <div class="stat-content">
                <h3>{{ stats.remainingSessions }}</h3>
                <p>Sessions Left</p>
              </div>
            </div>
          </div>
          
          <div class="col-md-3 mb-3">
            <div class="stat-card bg-info text-white">
              <div class="stat-icon">
                <i class="fas fa-box"></i>
              </div>
              <div class="stat-content">
                <h3>{{ stats.activePackages }}</h3>
                <p>Active Packages</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Next Class -->
        <div v-if="nextClass" class="next-class-section mb-4">
          <h4 class="mb-3">Your Next Class</h4>
          <div class="next-class-card">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h5 class="mb-2">{{ nextClass.gymClass.name }}</h5>
                <div class="class-details">
                  <span class="me-3">
                    <i class="fas fa-calendar me-1"></i> 
                    {{ formatDate(nextClass.scheduled_at) }}
                  </span>
                  <span class="me-3">
                    <i class="fas fa-clock me-1"></i> 
                    {{ formatTime(nextClass.scheduled_at) }}
                  </span>
                  <span>
                    <i class="fas fa-user me-1"></i> 
                    {{ nextClass.gymClass.instructor.name }}
                  </span>
                </div>
              </div>
              <div class="col-md-4 text-md-end">
                <button class="btn btn-primary">
                  <i class="fas fa-map-marker-alt me-1"></i> View Details
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions mb-4">
          <h4 class="mb-3">Quick Actions</h4>
          <div class="row">
            <div class="col-md-4 mb-3">
              <button @click="currentView = 'bookings'" class="action-card w-100">
                <i class="fas fa-calendar-plus fa-2x mb-2"></i>
                <h6>Book a Class</h6>
                <p class="mb-0 text-muted">Reserve your spot in upcoming classes</p>
              </button>
            </div>
            <div class="col-md-4 mb-3">
              <button @click="currentView = 'profile'" class="action-card w-100">
                <i class="fas fa-user-edit fa-2x mb-2"></i>
                <h6>Update Profile</h6>
                <p class="mb-0 text-muted">Keep your information up to date</p>
              </button>
            </div>
            <div class="col-md-4 mb-3">
              <button class="action-card w-100">
                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                <h6>Buy Package</h6>
                <p class="mb-0 text-muted">Purchase new training packages</p>
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Profile Edit View -->
      <div v-if="currentView === 'profile'">
        <ProfileEdit />
      </div>
      
      <!-- Bookings View -->
      <div v-if="currentView === 'bookings'">
        <BookingManagement />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import ProfileEdit from './ProfileEdit.vue';
import BookingManagement from './BookingManagement.vue';

export default {
  name: 'ClientDashboard',
  
  components: {
    ProfileEdit,
    BookingManagement
  },
  
  data() {
    return {
      currentView: 'dashboard',
      userName: '',
      stats: {
        upcomingBookings: 0,
        completedSessions: 0,
        remainingSessions: 0,
        activePackages: 0
      },
      nextClass: null,
      loading: false
    };
  },
  
  mounted() {
    this.loadUserData();
  },
  
  methods: {
    async loadUserData() {
      this.loading = true;
      try {
        const response = await axios.get('/api/v1/profile');
        const userData = response.data;
        
        this.userName = userData.user.name;
        this.stats = userData.statistics;
        
        // Get next upcoming class
        if (userData.user.bookings && userData.user.bookings.length > 0) {
          this.nextClass = userData.user.bookings[0];
        }
      } catch (error) {
        console.error('Error loading user data:', error);
      } finally {
        this.loading = false;
      }
    },
    
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { 
        weekday: 'short', 
        month: 'short', 
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
    
    async logout() {
      try {
        await axios.post('/api/v1/auth/logout');
        window.location.href = '/login';
      } catch (error) {
        console.error('Error logging out:', error);
      }
    }
  }
};
</script>

<style scoped>
.client-dashboard {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.main-content {
  min-height: calc(100vh - 56px);
}

/* Stat Cards */
.stat-card {
  padding: 1.5rem;
  border-radius: 10px;
  display: flex;
  align-items: center;
  height: 100%;
  transition: transform 0.3s;
}

.stat-card:hover {
  transform: translateY(-5px);
}

.stat-icon {
  font-size: 2.5rem;
  margin-right: 1rem;
  opacity: 0.8;
}

.stat-content h3 {
  margin-bottom: 0.25rem;
  font-size: 2rem;
}

.stat-content p {
  margin-bottom: 0;
  font-size: 0.9rem;
  opacity: 0.9;
}

/* Next Class Card */
.next-class-card {
  background: white;
  padding: 1.5rem;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.class-details {
  color: #6c757d;
  font-size: 0.9rem;
}

/* Quick Actions */
.action-card {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 10px;
  padding: 2rem;
  text-align: center;
  transition: all 0.3s;
  cursor: pointer;
}

.action-card:hover {
  border-color: #007bff;
  box-shadow: 0 5px 15px rgba(0,123,255,0.1);
  transform: translateY(-3px);
}

.action-card i {
  color: #007bff;
}

.action-card h6 {
  color: #212529;
  margin-bottom: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
  .stat-card {
    margin-bottom: 1rem;
  }
  
  .next-class-card .row > div {
    text-align: center;
    margin-bottom: 1rem;
  }
}
</style>