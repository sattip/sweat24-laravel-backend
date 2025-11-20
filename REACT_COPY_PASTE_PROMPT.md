# React Frontend Development - Copy & Paste Ready Prompt

## 🚀 GYM MANAGEMENT SYSTEM - REACT APP

**Create a modern React application for gym services with Custom Package Management & Smart Booking Validation**

---

## ⚡ QUICK START

```bash
# Project Setup
npx create-next-app@latest gym-frontend --typescript --tailwind --app
cd gym-frontend

# Install Dependencies
npm install @tanstack/react-query @hookform/resolvers zod react-hook-form lucide-react @radix-ui/react-* react-hot-toast @tanstack/react-table date-fns clsx tailwind-merge
npm install -D @types/node eslint-config-next prettier eslint-plugin-prettier

# UI Components Setup
npx shadcn-ui@latest init
npx shadcn-ui@latest add button input card table dialog select badge alert
```

---

## 🏗️ PROJECT STRUCTURE

```
/app
├── (auth)/login/page.tsx
├── (dashboard)/
│   ├── admin/custom-packages/page.tsx
│   ├── user/dashboard/page.tsx
│   ├── layout.tsx
├── globals.css
/components
├── ui/button.tsx, input.tsx, card.tsx...
├── forms/CustomPackageForm.tsx
├── tables/CustomPackageTable.tsx
/lib
├── api/client.ts
├── validations/schemas.ts
├── utils/cn.ts
/types
├── custom-package.ts
├── booking.ts
```

---

## 🎨 DESIGN SYSTEM

```css
:root {
  --primary: 59 130 246;    /* Blue-500 */
  --secondary: 245 158 11;  /* Amber-500 */
  --success: 16 185 129;    /* Emerald-500 */
  --error: 239 68 68;       /* Red-500 */
  --background: 255 255 255;
  --foreground: 15 23 42;   /* Slate-900 */
}
```

---

## 🔐 AUTHENTICATION

```typescript
// lib/auth.ts
export const auth = {
  login: async (credentials) => api.post('/api/v1/auth/login', credentials),
  logout: () => { localStorage.removeItem('token'); router.push('/login'); },
  getUser: () => api.get('/api/v1/auth/me'),
  isAdmin: () => user?.role === 'admin'
};
```

---

## 💎 CUSTOM PACKAGE MANAGEMENT

### Core Types
```typescript
interface CustomPackage {
  id: number;
  user_id: number;
  package_id: number;
  custom_price: number;
  custom_sessions: number;
  custom_duration_days: number;
  custom_notes?: string;
  assigned_by: string;
  assigned_at: string;
  status: 'active' | 'inactive' | 'expired';
}

interface CustomPackageForm {
  userId: number;
  packageId: number;
  customPrice: number;
  customSessions: number;
  customDurationDays: number;
  customNotes?: string;
}
```

### API Client
```typescript
// lib/api/custom-packages.ts
export const customPackageAPI = {
  getEligibleUsers: () => api.get('/api/v1/custom-packages/users'),
  getAvailablePackages: () => api.get('/api/v1/custom-packages/available-packages'),
  getUserCustomPackages: (userId: number) => api.get(`/api/v1/custom-packages/user/${userId}`),
  create: (data: CustomPackageForm) => api.post('/api/v1/custom-packages', data),
  update: (id: number, data: Partial<CustomPackageForm>) => api.put(`/api/v1/custom-packages/${id}`, data),
  delete: (id: number) => api.delete(`/api/v1/custom-packages/${id}`),
};
```

### Main Component
```typescript
// components/forms/CustomPackageForm.tsx
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

const schema = z.object({
  userId: z.number().min(1, 'Select a user'),
  packageId: z.number().min(1, 'Select a package'),
  customPrice: z.number().min(0, 'Price must be positive'),
  customSessions: z.number().min(1, 'At least 1 session'),
  customDurationDays: z.number().min(1, 'At least 1 day'),
  customNotes: z.string().optional(),
});

export function CustomPackageForm() {
  const { register, handleSubmit, watch, formState: { errors } } = useForm({
    resolver: zodResolver(schema),
  });

  const watchedPrice = watch('customPrice');
  const watchedOriginalPrice = watch('packageId'); // Get from selected package

  const savings = watchedOriginalPrice ? (watchedOriginalPrice - watchedPrice) : 0;

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <div className="grid grid-cols-2 gap-4">
        <UserSelector {...register('userId')} />
        <PackageSelector {...register('packageId')} />
      </div>

      <div className="grid grid-cols-3 gap-4">
        <Input {...register('customPrice')} placeholder="Custom Price (€)" />
        <Input {...register('customSessions')} placeholder="Sessions" />
        <Input {...register('customDurationDays')} placeholder="Days" />
      </div>

      {savings > 0 && (
        <div className="bg-green-50 p-4 rounded-lg">
          <p className="text-green-800 font-medium">
            💰 Customer saves €{savings.toFixed(2)}
          </p>
        </div>
      )}

      <Textarea {...register('customNotes')} placeholder="Notes (optional)" />

      <Button type="submit" className="w-full">
        Create Custom Package
      </Button>
    </form>
  );
}
```

### Admin Page
```typescript
// app/(dashboard)/admin/custom-packages/page.tsx
'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { CustomPackageTable } from '@/components/tables/CustomPackageTable';
import { CustomPackageForm } from '@/components/forms/CustomPackageForm';

export default function CustomPackagesPage() {
  const queryClient = useQueryClient();

  const { data: customPackages, isLoading } = useQuery({
    queryKey: ['custom-packages'],
    queryFn: () => customPackageAPI.getUserCustomPackages(userId),
  });

  const createMutation = useMutation({
    mutationFn: customPackageAPI.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['custom-packages'] });
      toast.success('Custom package created successfully!');
    },
  });

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Custom Package Management</h1>
        <Badge variant="secondary">VIP Customers: {customPackages?.length || 0}</Badge>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Assign Custom Package</CardTitle>
          <CardDescription>
            Create personalized packages with custom pricing and terms
          </CardDescription>
        </CardHeader>
        <CardContent>
          <CustomPackageForm onSubmit={createMutation.mutate} />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Custom Packages</CardTitle>
        </CardHeader>
        <CardContent>
          <CustomPackageTable
            data={customPackages || []}
            onEdit={(pkg) => {/* Handle edit */}}
            onDelete={(id) => {/* Handle delete */}}
          />
        </CardContent>
      </Card>
    </div>
  );
}
```

---

## 🎫 SMART BOOKING SYSTEM

### Booking Types
```typescript
interface BookingRequest {
  user_id: number;
  class_id: number;
  class_name: string;
  service_id?: number;
  date: string;
  time: string;
}

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

### Booking Component
```typescript
// components/booking/BookingForm.tsx
export function BookingForm({ userId }: { userId: number }) {
  const [selectedService, setSelectedService] = useState<number | null>(null);
  const [validation, setValidation] = useState<BookingValidation | null>(null);

  const { data: userPackages } = useQuery({
    queryKey: ['user-packages', userId],
    queryFn: () => api.get(`/api/v1/users/${userId}/packages`),
  });

  const validateBooking = useMutation({
    mutationFn: (data: BookingRequest) => api.post('/api/v1/bookings/validate', data),
    onSuccess: (result) => setValidation(result),
  });

  const handleServiceChange = (serviceId: number) => {
    setSelectedService(serviceId);
    // Auto-detect service if not provided
    if (!selectedService) {
      // Smart detection logic here
    }
  };

  return (
    <form className="space-y-6">
      <ServiceSelector
        value={selectedService}
        onChange={handleServiceChange}
        services={services}
      />

      <ClassSelector
        serviceId={selectedService}
        onClassSelect={(classData) => {
          validateBooking.mutate({
            user_id: userId,
            class_id: classData.id,
            class_name: classData.name,
            date: selectedDate,
            time: selectedTime,
          });
        }}
      />

      {validation && !validation.hasValidPackage && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Package Required</AlertTitle>
          <AlertDescription>
            {validation.errorMessage}
            <Button variant="link" className="p-0 ml-2">
              View Available Packages
            </Button>
          </AlertDescription>
        </Alert>
      )}

      <PackageStatusCard
        validation={validation}
        userPackages={userPackages}
      />

      <Button
        type="submit"
        disabled={!validation?.hasValidPackage}
        className="w-full"
      >
        {validation?.hasValidPackage ? 'Confirm Booking' : 'Select Valid Package'}
      </Button>
    </form>
  );
}
```

---

## 👤 USER PROFILE ENHANCEMENT

### VIP Profile Component
```typescript
// components/user/VIPProfileCard.tsx
export function VIPProfileCard({ user }: { user: User }) {
  const { data: customPackages } = useQuery({
    queryKey: ['user-custom-packages', user.id],
    queryFn: () => customPackageAPI.getUserCustomPackages(user.id),
  });

  const hasVIPStatus = customPackages && customPackages.length > 0;
  const totalSavings = customPackages?.reduce((sum, pkg) => sum + pkg.savings, 0) || 0;

  return (
    <Card className={hasVIPStatus ? 'border-yellow-200 bg-yellow-50' : ''}>
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="flex items-center gap-2">
            {user.name}
            {hasVIPStatus && <Crown className="h-5 w-5 text-yellow-600" />}
          </CardTitle>
          {hasVIPStatus && (
            <Badge variant="secondary" className="bg-yellow-100 text-yellow-800">
              👑 VIP Customer
            </Badge>
          )}
        </div>
      </CardHeader>

      <CardContent className="space-y-4">
        {hasVIPStatus && (
          <div className="bg-yellow-100 p-4 rounded-lg">
            <h3 className="font-semibold text-yellow-800">VIP Benefits</h3>
            <p className="text-yellow-700">
              💰 Total Savings: €{totalSavings.toFixed(2)}
            </p>
            <p className="text-yellow-700">
              📦 Custom Packages: {customPackages.length}
            </p>
          </div>
        )}

        <CustomPackageList packages={customPackages || []} />
      </CardContent>
    </Card>
  );
}
```

---

## 🗂️ DATA TABLES

### Custom Package Table
```typescript
// components/tables/CustomPackageTable.tsx
import { useTable, useSortBy, useFilters } from '@tanstack/react-table';

const columns = [
  {
    accessorKey: 'user_name',
    header: 'Customer',
    cell: ({ row }) => (
      <div className="flex items-center gap-2">
        {row.original.user_name}
        {row.original.is_vip && <Crown className="h-4 w-4 text-yellow-600" />}
      </div>
    ),
  },
  {
    accessorKey: 'custom_price',
    header: 'Custom Price',
    cell: ({ row }) => `€${row.original.custom_price}`,
  },
  {
    accessorKey: 'savings',
    header: 'Savings',
    cell: ({ row }) => (
      <Badge variant="secondary" className="bg-green-100 text-green-800">
        €{row.original.savings}
      </Badge>
    ),
  },
  {
    accessorKey: 'assigned_at',
    header: 'Assigned',
    cell: ({ row }) => format(new Date(row.original.assigned_at), 'MMM dd, yyyy'),
  },
];

export function CustomPackageTable({ data }: { data: CustomPackage[] }) {
  const table = useTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
  });

  return (
    <div className="rounded-md border">
      <Table>
        <TableHeader>
          {table.getHeaderGroups().map((headerGroup) => (
            <TableRow key={headerGroup.id}>
              {headerGroup.headers.map((header) => (
                <TableHead key={header.id}>
                  {header.isPlaceholder ? null : header.column.columnDef.header}
                </TableHead>
              ))}
            </TableRow>
          ))}
        </TableHeader>
        <TableBody>
          {table.getRowModel().rows.map((row) => (
            <TableRow key={row.id}>
              {row.getVisibleCells().map((cell) => (
                <TableCell key={cell.id}>
                  {cell.column.columnDef.cell?.(cell) ?? cell.getValue()}
                </TableCell>
              ))}
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}
```

---

## 🔗 API CLIENT SETUP

```typescript
// lib/api/client.ts
import { QueryClient } from '@tanstack/react-query';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      retry: 1,
    },
  },
});

class APIClient {
  private baseURL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

  private async request(endpoint: string, options: RequestInit = {}) {
    const token = localStorage.getItem('token');

    const response = await fetch(`${this.baseURL}${endpoint}`, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...(token && { Authorization: `Bearer ${token}` }),
        ...options.headers,
      },
    });

    if (!response.ok) {
      throw new Error(`API Error: ${response.status}`);
    }

    return response.json();
  }

  get(endpoint: string) {
    return this.request(endpoint);
  }

  post(endpoint: string, data: any) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  put(endpoint: string, data: any) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  delete(endpoint: string) {
    return this.request(endpoint, { method: 'DELETE' });
  }
}

export const api = new APIClient();
```

---

## 🎨 STYLING & THEMING

```typescript
// lib/utils/cn.ts
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}
```

```typescript
// components/ui/button.tsx
import { forwardRef } from 'react';
import { cn } from '@/lib/utils/cn';

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
  size?: 'default' | 'sm' | 'lg' | 'icon';
}

const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant = 'default', size = 'default', ...props }, ref) => {
    return (
      <button
        className={cn(
          'inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
          {
            'bg-primary text-primary-foreground hover:bg-primary/90': variant === 'default',
            'bg-destructive text-destructive-foreground hover:bg-destructive/90': variant === 'destructive',
            'border border-input bg-background hover:bg-accent hover:text-accent-foreground': variant === 'outline',
            'bg-secondary text-secondary-foreground hover:bg-secondary/80': variant === 'secondary',
            'hover:bg-accent hover:text-accent-foreground': variant === 'ghost',
            'text-primary underline-offset-4 hover:underline': variant === 'link',
          },
          {
            'h-10 px-4 py-2': size === 'default',
            'h-9 rounded-md px-3': size === 'sm',
            'h-11 rounded-md px-8': size === 'lg',
            'h-10 w-10': size === 'icon',
          },
          className
        )}
        ref={ref}
        {...props}
      />
    );
  }
);

Button.displayName = 'Button';

export { Button };
```

---

## 🧪 TESTING SETUP

```typescript
// __tests__/CustomPackageForm.test.tsx
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { CustomPackageForm } from '@/components/forms/CustomPackageForm';

describe('CustomPackageForm', () => {
  it('should validate required fields', async () => {
    render(<CustomPackageForm onSubmit={jest.fn()} />);

    const submitButton = screen.getByRole('button', { name: /create/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByText('Select a user')).toBeInTheDocument();
      expect(screen.getByText('Select a package')).toBeInTheDocument();
    });
  });

  it('should calculate savings correctly', () => {
    render(<CustomPackageForm onSubmit={jest.fn()} />);

    // Mock package selection with original price €200
    // Set custom price to €150
    // Expect savings display to show €50
    expect(screen.getByText('💰 Customer saves €50.00')).toBeInTheDocument();
  });
});
```

---

## 🚀 QUICK IMPLEMENTATION GUIDE

### 1. Setup Project
```bash
npx create-next-app@latest gym-app --typescript --tailwind --app
cd gym-app
npm install @tanstack/react-query react-hook-form zod @radix-ui/react-dialog @radix-ui/react-select lucide-react
```

### 2. Create Core Components
```bash
# Create directories
mkdir -p components/ui components/forms components/tables lib/api types

# Add UI components
npx shadcn-ui@latest add button card input table dialog
```

### 3. Implement Custom Package Feature
1. Create `CustomPackageForm.tsx`
2. Create `CustomPackageTable.tsx`
3. Create API client functions
4. Create admin page
5. Add routing

### 4. Implement Smart Booking
1. Create booking validation logic
2. Add service detection
3. Create booking form with real-time validation
4. Add package status display

### 5. Enhance User Profile
1. Add VIP indicators
2. Show custom package benefits
3. Display savings information
4. Add personalized dashboard

---

## 🎯 KEY FEATURES SUMMARY

✅ **Custom Package Management**
- Create personalized packages
- Calculate customer savings
- VIP customer tracking
- Admin audit trail

✅ **Smart Booking System**
- Auto-detect services from class names
- Real-time package validation
- Clear error messages
- Session tracking

✅ **VIP Customer Experience**
- Special UI indicators
- Personalized pricing display
- Savings visualization
- Enhanced booking flow

✅ **Modern Tech Stack**
- Next.js 14 + TypeScript
- Tailwind + shadcn/ui
- React Query for state
- Form validation with Zod

---

## 📋 CHECKLIST

- [ ] Project setup with Next.js + TypeScript
- [ ] Authentication system
- [ ] Custom Package Form component
- [ ] Custom Package Table component
- [ ] Smart Booking validation
- [ ] User Profile enhancement
- [ ] API integration
- [ ] Responsive design
- [ ] Testing setup
- [ ] Performance optimization

---

**🎉 Ready to copy & paste! Start building your gym management React app now!**




