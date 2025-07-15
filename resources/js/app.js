import './bootstrap';
import { createApp } from 'vue';

// Client Components
import ClientDashboard from './components/client/ClientDashboard.vue';
import ProfileEdit from './components/client/ProfileEdit.vue';
import BookingManagement from './components/client/BookingManagement.vue';

const app = createApp({});

// Register client components
app.component('client-dashboard', ClientDashboard);
app.component('profile-edit', ProfileEdit);
app.component('booking-management', BookingManagement);

app.mount('#app');
