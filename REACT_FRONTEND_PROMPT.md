# 🚀 React Frontend Development Prompt
## Gym Management System - Custom Package & Booking Management

### 🎯 **Project Overview**
Create a modern React application for managing gym services with advanced custom package assignment and intelligent booking validation. The app should provide administrators with powerful tools for VIP customer management and seamless booking experiences for users.

---

## 🛠️ **Technical Stack Requirements**

### **Core Technologies**
- **React 18+** with TypeScript
- **Next.js 14+** (App Router)
- **Tailwind CSS** for styling
- **React Hook Form** + **Zod** for form validation
- **TanStack Query (React Query)** for API state management
- **React Router** for navigation

### **UI/UX Libraries**
- **shadcn/ui** + **Radix UI** components
- **Lucide React** for icons
- **React Hot Toast** for notifications
- **React Table** for data tables
- **React Date Picker** for date selection

### **Development Tools**
- **ESLint** + **Prettier** for code quality
- **Husky** + **lint-staged** for pre-commit hooks
- **Storybook** for component documentation

---

## 📁 **Project Structure**

```
gym-management-frontend/
├── app/                          # Next.js App Router
│   ├── (auth)/                   # Authentication routes
│   ├── (dashboard)/              # Protected routes
│   │   ├── admin/                # Admin-only pages
│   │   ├── user/                 # User pages
│   │   └── layout.tsx
│   ├── api/                      # API routes (if needed)
│   └── globals.css
├── components/                   # Reusable components
│   ├── ui/                       # Base UI components
│   ├── forms/                    # Form components
│   ├── tables/                   # Data table components
│   └── layout/                   # Layout components
├── hooks/                        # Custom React hooks
├── lib/                          # Utility functions
│   ├── api/                      # API client functions
│   ├── utils/                    # Helper functions
│   └── validations/              # Zod schemas
├── types/                        # TypeScript type definitions
├── constants/                    # App constants
└── public/                       # Static assets
```

---

## 🎨 **Design System & UI Requirements**

### **Color Palette**
```css
--primary: #3b82f6 (Blue-500)
--secondary: #f59e0b (Amber-500)
--success: #10b981 (Emerald-500)
--warning: #f59e0b (Amber-500)
--error: #ef4444 (Red-500)
--background: #ffffff
--surface: #f8fafc
--text-primary: #1e293b
--text-secondary: #64748b
```

### **Typography**
- **Primary Font**: Inter (Google Fonts)
- **Heading Sizes**: 2xl, xl, lg, base
- **Body Text**: sm, base
- **Weights**: 400, 500, 600, 700

### **Component Guidelines**
- **Border Radius**: 8px for cards, 6px for buttons
- **Shadows**: Subtle shadows for depth
- **Spacing**: 4px base unit (4, 8, 12, 16, 24, 32, 48, 64)
- **Responsive**: Mobile-first approach

---

## 🔐 **Authentication & Authorization**

### **Role-Based Access**
```typescript
enum UserRole {
  ADMIN = 'admin',
  TRAINER = 'trainer',
  USER = 'user'
}

enum Permission {
  MANAGE_CUSTOM_PACKAGES = 'manage_custom_packages',
  VIEW_ALL_BOOKINGS = 'view_all_bookings',
  MANAGE_USERS = 'manage_users',
  VIEW_ANALYTICS = 'view_analytics'
}
```

### **Protected Routes Structure**
- **Public Routes**: Login, Register, Forgot Password
- **User Routes**: Dashboard, My Bookings, My Packages, Profile
- **Admin Routes**: User Management, Custom Packages, Analytics, Settings
- **Trainer Routes**: Class Management, Student Progress

---

## 💎 **Core Features Implementation**

### **1. Custom Package Management (Admin Only)**

#### **Custom Package Assignment Form**
```typescript
interface CustomPackageFormData {
  userId: number;
  packageId: number;
  customPrice: number;        // In cents
  customSessions: number;
  customDurationDays: number;
  customNotes?: string;
  assignedBy: string;
}
```

#### **Required Components**
- ✅ **UserSelector**: Dropdown with search for selecting users
- ✅ **PackageSelector**: Available packages with original pricing
- ✅ **CustomPackageForm**: Form for setting custom terms
- ✅ **PriceComparison**: Side-by-side comparison of original vs custom pricing
- ✅ **SavingsCalculator**: Real-time calculation of customer savings
- ✅ **CustomPackageTable**: Data table showing all custom packages
- ✅ **VIPIndicator**: Special badge for users with custom packages

#### **Key User Flows**
1. **Select User** → Choose from eligible users list
2. **Choose Base Package** → Select from available packages
3. **Set Custom Terms** → Modify price, sessions, duration
4. **Add Notes** → Optional notes about the custom assignment
5. **Confirm Assignment** → Review and save custom package

### **2. Enhanced Booking System**

#### **Smart Booking Validation**
```typescript
interface BookingValidation {
  hasValidPackage: boolean;
  serviceId: number | null;
  remainingSessions: number;
  packageExpiry: Date | null;
  detectedService?: string;
  errorCode?: string;
  errorMessage?: string;
}
```

#### **Required Components**
- ✅ **ServiceSelector**: Dropdown with available services
- ✅ **ClassSelector**: Available classes filtered by selected service
- ✅ **BookingCalendar**: Interactive calendar with availability
- ✅ **PackageStatusCard**: Shows user's package status for selected service
- ✅ **SessionCounter**: Displays remaining sessions
- ✅ **SmartValidation**: Real-time validation with helpful error messages

#### **Booking Flow Enhancement**
1. **Service Selection** → Choose service (auto-detects from class name if needed)
2. **Package Validation** → Checks if user has valid package for service
3. **Session Verification** → Ensures sufficient remaining sessions
4. **Smart Error Handling** → Clear, actionable error messages
5. **Booking Confirmation** → Summary with package deduction preview

### **3. User Profile Enhancement**

#### **VIP Customer Features**
- ✅ **Custom Package Badge**: Special indicator for VIP customers
- ✅ **Personalized Pricing**: Shows custom vs original pricing
- ✅ **Savings Display**: Highlights money saved with custom packages
- ✅ **Special Terms**: Custom duration and session counts
- ✅ **Assignment History**: Shows who assigned custom package and when

#### **Profile Components**
- ✅ **UserProfileCard**: Main profile information
- ✅ **PackageHistoryTable**: All user's packages (regular + custom)
- ✅ **BookingHistoryTable**: User's booking history
- ✅ **CustomPackageDetails**: Detailed view of custom package terms

---

## 🔗 **API Integration Requirements**

### **Custom Package Endpoints**
```typescript
// API Client Functions
const customPackageAPI = {
  // Get eligible users
  getEligibleUsers: () => api.get('/api/v1/custom-packages/users'),

  // Get available packages
  getAvailablePackages: () => api.get('/api/v1/custom-packages/available-packages'),

  // Get user's custom packages
  getUserCustomPackages: (userId: number) =>
    api.get(`/api/v1/custom-packages/user/${userId}`),

  // Create custom package
  createCustomPackage: (data: CustomPackageFormData) =>
    api.post('/api/v1/custom-packages', data),

  // Update custom package
  updateCustomPackage: (id: number, data: Partial<CustomPackageFormData>) =>
    api.put(`/api/v1/custom-packages/${id}`, data),

  // Delete custom package
  deleteCustomPackage: (id: number) =>
    api.delete(`/api/v1/custom-packages/${id}`),
};
```

### **Enhanced Booking Endpoints**
```typescript
const bookingAPI = {
  // Validate booking before creation
  validateBooking: (bookingData: BookingRequest) =>
    api.post('/api/v1/bookings/validate', bookingData),

  // Get user's packages for service
  getUserPackagesForService: (userId: number, serviceId: number) =>
    api.get(`/api/v1/users/${userId}/packages/service/${serviceId}`),

  // Smart service detection
  detectService: (className: string) =>
    api.post('/api/v1/services/detect', { class_name: className }),
};
```

---

## 📊 **Data Tables & Analytics**

### **Custom Package Management Table**
```typescript
interface CustomPackageTableColumn {
  id: 'user_name' | 'package_name' | 'custom_price' | 'savings' | 'assigned_at' | 'actions';
  label: string;
  sortable: boolean;
  filterable: boolean;
}
```

### **Advanced Filtering & Search**
- ✅ **User Search**: Search by name, email
- ✅ **Package Filter**: Filter by package type, service
- ✅ **Date Range**: Filter by assignment date
- ✅ **Status Filter**: Active, expired, cancelled
- ✅ **Price Range**: Filter by custom price range

### **Analytics Dashboard**
- ✅ **Custom Package Statistics**: Total custom packages, total savings
- ✅ **VIP Customer Metrics**: Number of VIP customers, average savings
- ✅ **Booking Success Rate**: Bookings with valid packages vs total
- ✅ **Revenue Impact**: Revenue from custom packages

---

## 🧪 **Testing Requirements**

### **Unit Tests**
```typescript
// Component Testing
describe('CustomPackageForm', () => {
  it('should validate required fields', () => {});
  it('should calculate savings correctly', () => {});
  it('should show price comparison', () => {});
});

// Hook Testing
describe('useCustomPackages', () => {
  it('should fetch user custom packages', () => {});
  it('should handle loading states', () => {});
  it('should handle error states', () => {});
});
```

### **Integration Tests**
- ✅ **Custom Package Creation Flow**
- ✅ **Booking Validation Flow**
- ✅ **User Profile Loading**
- ✅ **Admin Dashboard Data**

### **E2E Tests**
- ✅ **Complete Custom Package Assignment**
- ✅ **Booking with Package Validation**
- ✅ **User Profile with VIP Features**

---

## 🚀 **Performance Optimization**

### **Code Splitting**
- ✅ **Route-based splitting** for different user roles
- ✅ **Component lazy loading** for heavy components
- ✅ **Vendor chunk separation**

### **Caching Strategy**
- ✅ **React Query caching** for API responses
- ✅ **Local storage** for user preferences
- ✅ **Service worker** for offline capability

### **Optimization Techniques**
- ✅ **Memoization** with React.memo and useMemo
- ✅ **Virtual scrolling** for large tables
- ✅ **Image optimization** with Next.js Image component
- ✅ **Bundle analysis** with webpack-bundle-analyzer

---

## 📱 **Responsive Design**

### **Breakpoint Strategy**
```css
/* Mobile First Approach */
--mobile: 320px;
--tablet: 768px;
--desktop: 1024px;
--wide: 1440px;
```

### **Mobile Optimizations**
- ✅ **Touch-friendly buttons** (minimum 44px height)
- ✅ **Swipe gestures** for table navigation
- ✅ **Collapsible sidebar** for mobile navigation
- ✅ **Optimized forms** for mobile input

### **Tablet & Desktop**
- ✅ **Multi-column layouts** for better space utilization
- ✅ **Advanced table features** (sorting, filtering, pagination)
- ✅ **Keyboard shortcuts** for power users
- ✅ **Drag & drop** for reordering

---

## 🔒 **Security Considerations**

### **Frontend Security**
- ✅ **Input sanitization** for all user inputs
- ✅ **CSRF protection** with Next.js built-in features
- ✅ **XSS prevention** with proper escaping
- ✅ **Secure local storage** for sensitive data

### **API Security**
- ✅ **JWT token management** with automatic refresh
- ✅ **Request/response interception** for error handling
- ✅ **Rate limiting awareness** in UI
- ✅ **Proper error messages** without data leakage

---

## 🎯 **Success Metrics**

### **Performance Targets**
- ✅ **First Contentful Paint**: < 1.5s
- ✅ **Largest Contentful Paint**: < 2.5s
- ✅ **Cumulative Layout Shift**: < 0.1
- ✅ **First Input Delay**: < 100ms

### **User Experience Goals**
- ✅ **Task Completion Rate**: > 95%
- ✅ **Error Rate**: < 2%
- ✅ **User Satisfaction Score**: > 4.5/5
- ✅ **Mobile Usability**: 100% mobile-friendly

---

## 📋 **Development Phases**

### **Phase 1: Foundation (Week 1-2)**
- ✅ Project setup with Next.js + TypeScript
- ✅ Authentication system
- ✅ Basic layout and navigation
- ✅ API client setup

### **Phase 2: Core Features (Week 3-6)**
- ✅ Custom Package Management
- ✅ Enhanced Booking System
- ✅ User Profile Enhancement
- ✅ Admin Dashboard

### **Phase 3: Advanced Features (Week 7-8)**
- ✅ Analytics and reporting
- ✅ Advanced filtering and search
- ✅ Performance optimization
- ✅ Testing implementation

### **Phase 4: Polish & Launch (Week 9-10)**
- ✅ UI/UX refinements
- ✅ Mobile optimization
- ✅ Documentation
- ✅ Production deployment

---

## 📚 **Additional Resources**

### **Design References**
- [shadcn/ui Documentation](https://ui.shadcn.com/)
- [Radix UI Primitives](https://www.radix-ui.com/)
- [Tailwind CSS Guide](https://tailwindcss.com/docs)

### **Development Guidelines**
- [Next.js Documentation](https://nextjs.org/docs)
- [React Best Practices](https://react.dev/learn)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)

### **API Documentation**
- [Backend API Docs](./api-docs.yml)
- [Admin API Guide](./ADMIN_API_GUIDE.md)

---

## 🎉 **Final Deliverables**

### **Code Quality**
- ✅ **100% TypeScript coverage**
- ✅ **ESLint + Prettier configuration**
- ✅ **Comprehensive test suite**
- ✅ **Storybook component documentation**

### **Documentation**
- ✅ **README with setup instructions**
- ✅ **API integration guide**
- ✅ **Component documentation**
- ✅ **Deployment guide**

### **Performance**
- ✅ **Lighthouse score > 90**
- ✅ **Bundle size < 500KB**
- ✅ **Zero accessibility issues**
- ✅ **Mobile-friendly design**

---

**🎯 This React application will provide a powerful, user-friendly interface for managing gym services with advanced custom package capabilities and intelligent booking validation, delivering an exceptional experience for both administrators and users.**




