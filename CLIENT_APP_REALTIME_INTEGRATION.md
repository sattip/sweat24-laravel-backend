# 📱 **ΟΔΗΓΟΣ REAL-TIME ΕΝΗΜΕΡΩΣΕΩΝ ΠΑΡΑΓΓΕΛΙΩΝ - CLIENT APP**

## 🎯 **ΠΕΡΙΓΡΑΦΗ**
Πώς το Client App μπορεί να λαμβάνει **real-time ενημερώσεις** για την κατάσταση των παραγγελιών πέρα από τις push notifications.

---

## 🔄 **ΔΙΑΘΕΣΙΜΕΣ ΜΕΘΟΔΟΙ ΕΝΗΜΕΡΩΣΗΣ**

### **1. 📱 Push Notifications (ΗΔΗ ΕΝΕΡΓΟ)**
- ✅ Αυτόματη αποστολή όταν αλλάζει status
- ✅ Εμφανίζεται στο notification tray
- ✅ Λειτουργεί ακόμα και όταν το app είναι κλειστό

### **2. 🌐 WebSocket Real-Time Updates (ΠΡΟΤΕΙΝΟΜΕΝΟ)**
- ✅ Άμεση ενημέρωση στο UI
- ✅ Real-time badge updates
- ✅ Live status changes

### **3. 🔄 API Polling (FALLBACK)**
- ✅ Τακτικό ελέγχο για αλλαγές
- ✅ Λειτουργεί παντού
- ✅ Backup λύση

---

## 🌐 **WEBSOCKET INTEGRATION (ΠΡΟΤΕΙΝΟΜΕΝΟ)**

### **WebSocket Configuration:**
```typescript
// WebSocket Connection Settings
const WEBSOCKET_CONFIG = {
  host: 'sweat93laravel.obs.com.gr',
  port: 443,
  scheme: 'wss', // secure WebSocket
  app_key: 'sweat24appkey',
  cluster: 'eu'
};
```

### **Installation:**
```bash
npm install laravel-echo pusher-js
# ή
yarn add laravel-echo pusher-js
```

### **Setup Echo Client:**
```typescript
// src/services/websocket.ts
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
  broadcaster: 'reverb',
  key: 'sweat24appkey',
  wsHost: 'sweat93laravel.obs.com.gr',
  wsPort: 443,
  wssPort: 443,
  forceTLS: true,
  enabledTransports: ['ws', 'wss'],
});

export default echo;
```

### **Listen for Order Updates:**
```typescript
// src/services/orderUpdates.ts
import echo from './websocket';

export class OrderUpdateService {
  private user_id: number;
  
  constructor(user_id: number) {
    this.user_id = user_id;
  }

  // Subscribe to order updates for specific user
  subscribeToOrderUpdates(callback: (data: any) => void) {
    echo.private(`order.${this.user_id}`)
        .listen('OrderStatusChanged', (data: any) => {
          console.log('Order status changed:', data);
          callback(data);
        });
  }

  // Unsubscribe
  unsubscribe() {
    echo.leave(`order.${this.user_id}`);
  }
}
```

### **React Component Example:**
```tsx
// OrderStatusComponent.tsx
import React, { useEffect, useState } from 'react';
import { OrderUpdateService } from '../services/orderUpdates';

interface Order {
  id: number;
  order_number: string;
  status: string;
  total: string;
}

const OrderStatusComponent: React.FC<{ userId: number }> = ({ userId }) => {
  const [orders, setOrders] = useState<Order[]>([]);
  const [orderUpdateService, setOrderUpdateService] = useState<OrderUpdateService | null>(null);

  useEffect(() => {
    // Initialize WebSocket service
    const service = new OrderUpdateService(userId);
    setOrderUpdateService(service);

    // Subscribe to real-time updates
    service.subscribeToOrderUpdates((data) => {
      // Update order in state
      setOrders(prevOrders => 
        prevOrders.map(order => 
          order.id === data.order_id 
            ? { ...order, status: data.new_status }
            : order
        )
      );

      // Show toast notification
      showToast(`Παραγγελία ${data.order_number}: ${data.message}`);
    });

    // Cleanup on unmount
    return () => {
      service.unsubscribe();
    };
  }, [userId]);

  return (
    <div>
      {orders.map(order => (
        <OrderCard key={order.id} order={order} />
      ))}
    </div>
  );
};
```

---

## 🔄 **API POLLING (FALLBACK METHOD)**

### **Polling Service:**
```typescript
// src/services/orderPolling.ts
export class OrderPollingService {
  private intervalId: NodeJS.Timeout | null = null;
  private lastChecked: Date = new Date();
  
  startPolling(userId: number, callback: (orders: any[]) => void, interval: number = 30000) {
    this.intervalId = setInterval(async () => {
      try {
        const response = await fetch(
          `https://sweat93laravel.obs.com.gr/api/v1/orders/history?user_id=${userId}`,
          {
            headers: {
              'Accept': 'application/json',
            }
          }
        );
        
        const data = await response.json();
        
        if (data.success) {
          // Filter orders updated since last check
          const updatedOrders = data.data.filter((order: any) => 
            new Date(order.updated_at) > this.lastChecked
          );
          
          if (updatedOrders.length > 0) {
            callback(updatedOrders);
          }
          
          this.lastChecked = new Date();
        }
      } catch (error) {
        console.error('Polling error:', error);
      }
    }, interval);
  }
  
  stopPolling() {
    if (this.intervalId) {
      clearInterval(this.intervalId);
      this.intervalId = null;
    }
  }
}
```

### **React Hook για Polling:**
```tsx
// hooks/useOrderPolling.ts
import { useEffect, useState } from 'react';
import { OrderPollingService } from '../services/orderPolling';

export const useOrderPolling = (userId: number, enabled: boolean = true) => {
  const [orders, setOrders] = useState<any[]>([]);
  const [pollingService] = useState(new OrderPollingService());

  useEffect(() => {
    if (enabled && userId) {
      pollingService.startPolling(userId, (updatedOrders) => {
        // Update orders state
        setOrders(prevOrders => {
          const newOrders = [...prevOrders];
          
          updatedOrders.forEach(updatedOrder => {
            const index = newOrders.findIndex(o => o.id === updatedOrder.id);
            if (index >= 0) {
              newOrders[index] = updatedOrder;
            }
          });
          
          return newOrders;
        });
      });
    }

    return () => {
      pollingService.stopPolling();
    };
  }, [userId, enabled]);

  return { orders };
};
```

---

## 🎨 **UI UPDATES & ANIMATIONS**

### **Status Badge Component:**
```tsx
// StatusBadge.tsx
import React from 'react';

interface StatusBadgeProps {
  status: string;
  animated?: boolean;
}

const StatusBadge: React.FC<StatusBadgeProps> = ({ status, animated = false }) => {
  const getStatusConfig = (status: string) => {
    switch (status) {
      case 'pending':
        return { color: 'bg-yellow-500', icon: '⏳', text: 'Εκκρεμής' };
      case 'processing':
        return { color: 'bg-blue-500', icon: '🔄', text: 'Σε Επεξεργασία' };
      case 'ready_for_pickup':
        return { color: 'bg-green-500', icon: '📦', text: 'Έτοιμη για Παραλαβή' };
      case 'completed':
        return { color: 'bg-green-600', icon: '✅', text: 'Ολοκληρωμένη' };
      case 'cancelled':
        return { color: 'bg-red-500', icon: '❌', text: 'Ακυρωμένη' };
      default:
        return { color: 'bg-gray-500', icon: '❓', text: 'Άγνωστη' };
    }
  };

  const config = getStatusConfig(status);

  return (
    <div className={`
      ${config.color} text-white px-3 py-1 rounded-full text-sm font-medium
      ${animated ? 'animate-pulse' : ''}
      transition-all duration-300
    `}>
      <span className="mr-1">{config.icon}</span>
      {config.text}
    </div>
  );
};
```

### **Order Card με Real-time Updates:**
```tsx
// OrderCard.tsx
import React, { useState, useEffect } from 'react';
import { StatusBadge } from './StatusBadge';

interface OrderCardProps {
  order: any;
}

const OrderCard: React.FC<OrderCardProps> = ({ order }) => {
  const [isUpdated, setIsUpdated] = useState(false);
  const [prevStatus, setPrevStatus] = useState(order.status);

  // Detect status changes and show animation
  useEffect(() => {
    if (order.status !== prevStatus) {
      setIsUpdated(true);
      setPrevStatus(order.status);
      
      // Remove animation after 2 seconds
      setTimeout(() => setIsUpdated(false), 2000);
    }
  }, [order.status, prevStatus]);

  return (
    <div className={`
      bg-white rounded-lg shadow-md p-4 mb-4 border-l-4
      ${isUpdated ? 'border-l-green-500 bg-green-50' : 'border-l-gray-200'}
      transition-all duration-500
    `}>
      <div className="flex justify-between items-start">
        <div>
          <h3 className="font-semibold">#{order.order_number}</h3>
          <p className="text-gray-600">Σύνολο: €{order.total}</p>
          <p className="text-sm text-gray-500">
            {new Date(order.created_at).toLocaleDateString('el-GR')}
          </p>
        </div>
        
        <StatusBadge 
          status={order.status} 
          animated={isUpdated}
        />
      </div>
      
      {isUpdated && (
        <div className="mt-2 text-sm text-green-600 font-medium">
          ✨ Η κατάσταση ενημερώθηκε!
        </div>
      )}
    </div>
  );
};
```

---

## 🔔 **TOAST NOTIFICATIONS**

### **Toast Service:**
```typescript
// services/toastService.ts
export class ToastService {
  static show(message: string, type: 'success' | 'info' | 'warning' | 'error' = 'info') {
    // Implementation depends on your toast library
    // Example with react-hot-toast:
    const toast = require('react-hot-toast');
    
    switch (type) {
      case 'success':
        toast.success(message);
        break;
      case 'error':
        toast.error(message);
        break;
      case 'warning':
        toast(message, { icon: '⚠️' });
        break;
      default:
        toast(message);
    }
  }
}
```

---

## 📱 **INTEGRATION WORKFLOW**

### **1. App Startup:**
```typescript
// App.tsx
useEffect(() => {
  const userId = getCurrentUserId();
  
  if (userId) {
    // Initialize WebSocket connection
    const orderService = new OrderUpdateService(userId);
    
    // Subscribe to real-time updates
    orderService.subscribeToOrderUpdates((data) => {
      // Update UI
      updateOrderInState(data);
      
      // Show toast
      ToastService.show(data.message, 'info');
    });
    
    // Fallback to polling if WebSocket fails
    const pollingService = new OrderPollingService();
    
    // Start polling as backup (every 2 minutes)
    setTimeout(() => {
      pollingService.startPolling(userId, handleOrderUpdates, 120000);
    }, 5000);
  }
}, []);
```

### **2. Order History Screen:**
```typescript
// OrderHistoryScreen.tsx
const OrderHistoryScreen = () => {
  const userId = getCurrentUserId();
  const [orders, setOrders] = useState([]);
  
  // Load initial data
  useEffect(() => {
    loadOrderHistory();
  }, []);
  
  // Subscribe to real-time updates
  useEffect(() => {
    const service = new OrderUpdateService(userId);
    
    service.subscribeToOrderUpdates((data) => {
      setOrders(prevOrders => 
        prevOrders.map(order => 
          order.id === data.order_id 
            ? { ...order, status: data.new_status, updated_at: new Date() }
            : order
        )
      );
    });
    
    return () => service.unsubscribe();
  }, [userId]);
  
  const loadOrderHistory = async () => {
    try {
      const response = await fetch(`/api/v1/orders/history?user_id=${userId}`);
      const data = await response.json();
      
      if (data.success) {
        setOrders(data.data);
      }
    } catch (error) {
      console.error('Error loading orders:', error);
    }
  };
  
  return (
    <div>
      <h1>Ιστορικό Παραγγελιών</h1>
      {orders.map(order => (
        <OrderCard key={order.id} order={order} />
      ))}
    </div>
  );
};
```

---

## ⚙️ **WEBSOCKET SERVER CONFIGURATION**

### **Environment Variables:**
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=sweat24
REVERB_APP_KEY=sweat24appkey
REVERB_APP_SECRET=sweat24secret
REVERB_HOST=sweat93laravel.obs.com.gr
REVERB_PORT=443
REVERB_SCHEME=https
```

### **WebSocket URL:**
```
wss://sweat93laravel.obs.com.gr:443/app/sweat24appkey
```

---

## 🔧 **TROUBLESHOOTING**

### **WebSocket Connection Issues:**
```typescript
// Check WebSocket connection
echo.connector.pusher.connection.bind('connected', () => {
  console.log('✅ WebSocket connected');
});

echo.connector.pusher.connection.bind('error', (error: any) => {
  console.error('❌ WebSocket error:', error);
  // Fallback to polling
  startPollingFallback();
});
```

### **Fallback Strategy:**
```typescript
const useOrderUpdates = (userId: number) => {
  const [connectionType, setConnectionType] = useState<'websocket' | 'polling'>('websocket');
  
  useEffect(() => {
    // Try WebSocket first
    try {
      const wsService = new OrderUpdateService(userId);
      wsService.subscribeToOrderUpdates(handleUpdate);
      setConnectionType('websocket');
    } catch (error) {
      // Fallback to polling
      console.warn('WebSocket failed, using polling:', error);
      const pollingService = new OrderPollingService();
      pollingService.startPolling(userId, handleUpdate);
      setConnectionType('polling');
    }
  }, [userId]);
  
  return { connectionType };
};
```

---

## 📊 **PERFORMANCE OPTIMIZATION**

### **Connection Management:**
```typescript
class ConnectionManager {
  private static instance: ConnectionManager;
  private wsService: OrderUpdateService | null = null;
  private pollingService: OrderPollingService | null = null;
  
  static getInstance() {
    if (!ConnectionManager.instance) {
      ConnectionManager.instance = new ConnectionManager();
    }
    return ConnectionManager.instance;
  }
  
  connect(userId: number) {
    // Use singleton pattern to avoid multiple connections
    if (!this.wsService) {
      this.wsService = new OrderUpdateService(userId);
    }
  }
  
  disconnect() {
    this.wsService?.unsubscribe();
    this.pollingService?.stopPolling();
  }
}
```

---

## ✅ **ΣΥΝΟΨΗ IMPLEMENTATION**

### **Για το Client App Agent:**

1. **Εγκατάσταση Dependencies:**
   ```bash
   npm install laravel-echo pusher-js react-hot-toast
   ```

2. **WebSocket Setup:**
   - Δημιουργία `websocket.ts` service
   - Configuration με Reverb settings

3. **Order Update Service:**
   - Subscribe στο channel `order.{user_id}`
   - Listen για `OrderStatusChanged` events

4. **UI Components:**
   - StatusBadge με animations
   - OrderCard με real-time updates
   - Toast notifications

5. **Fallback Polling:**
   - API polling κάθε 2 λεπτά
   - Backup όταν WebSocket αποτυγχάνει

### **Αποτέλεσμα:**
- ⚡ **Instant updates** όταν admin αλλάζει status
- 📱 **Push notifications** για background updates  
- 🎨 **Animated UI** για visual feedback
- 🔄 **Polling fallback** για αξιοπιστία

**Ο χρήστης θα βλέπει αμέσως την αλλαγή κατάστασης στο app του!** 