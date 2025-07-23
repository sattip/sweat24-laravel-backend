# 🔧 **ΟΔΗΓΙΕΣ ΔΙΟΡΘΩΣΗΣ NGINX - REACT SPA**

## 🎯 **ΣΤΟΧΟΣ**
Διόρθωση της ρύθμισης Nginx για το `sweat24.obs.com.gr` από PHP configuration σε React SPA configuration.

---

## 📋 **ΑΛΛΑΓΕΣ ΠΟΥ ΓΙΝΟΝΤΑΙ**

### **✅ ΚΥΡΙΕΣ ΑΛΛΑΓΕΣ:**

1. **try_files directive:**
   ```nginx
   # ΑΠΟ (PHP):
   try_files $uri $uri/ /index.php?$query_string;
   
   # ΣΕ (React SPA):
   try_files $uri $uri/ /index.html;
   ```

2. **Index configuration:**
   ```nginx
   # ΑΠΟ:
   index index.html index.htm index.php;
   
   # ΣΕ:
   index index.html;
   ```

3. **Αφαίρεση PHP configuration:**
   ```nginx
   # ΑΦΑΙΡΟΥΝΤΑΙ:
   error_page 404 /index.php;
   
   location ~ \.php$ {
       fastcgi_split_path_info ^(.+\.php)(/.+)$;
       fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
       fastcgi_index index.php;
       include fastcgi_params;
   }
   ```

### **➕ ΠΡΟΣΘΕΤΕΣ ΒΕΛΤΙΩΣΕΙΣ:**

4. **Static assets caching:**
   ```nginx
   location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
       expires 1y;
       add_header Cache-Control "public, immutable";
       try_files $uri =404;
   }
   ```

5. **API proxy (προαιρετικό):**
   ```nginx
   location /api/ {
       proxy_pass https://sweat93laravel.obs.com.gr/api/;
       proxy_set_header Host $host;
       proxy_set_header X-Real-IP $remote_addr;
       proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
       proxy_set_header X-Forwarded-Proto $scheme;
   }
   ```

---

## 🛠️ **ΕΝΤΟΛΕΣ ΕΦΑΡΜΟΓΗΣ**

### **Βήμα 1: Backup του υπάρχοντος αρχείου**
```bash
sudo cp /etc/nginx/sites-available/sweat24.obs.com.gr /etc/nginx/sites-available/sweat24.obs.com.gr.backup
```

### **Βήμα 2: Αντικατάσταση του αρχείου**
```bash
sudo cp nginx-sweat24-spa-config.conf /etc/nginx/sites-available/sweat24.obs.com.gr
```

### **Βήμα 3: Έλεγχος syntax**
```bash
sudo nginx -t
```

### **Βήμα 4: Reload Nginx**
```bash
sudo systemctl reload nginx
```

### **Βήμα 5: Έλεγχος status**
```bash
sudo systemctl status nginx
```

---

## ✅ **ΈΛΕΓΧΟΣ ΛΕΙΤΟΥΡΓΙΑΣ**

### **Τest SPA routing:**
```bash
curl -I https://sweat24.obs.com.gr/orders
curl -I https://sweat24.obs.com.gr/profile  
curl -I https://sweat24.obs.com.gr/store
```

**Αναμενόμενο αποτέλεσμα:** `HTTP/1.1 200 OK` για όλα τα URLs

### **Test static assets:**
```bash
curl -I https://sweat24.obs.com.gr/assets/index.js
```

**Αναμενόμενο αποτέλεσμα:** `Cache-Control: public, immutable`

---

## 🔍 **ΑΝΤΙΜΕΤΩΠΙΣΗ ΠΡΟΒΛΗΜΑΤΩΝ**

### **Αν το Nginx δεν κάνει reload:**
```bash
sudo nginx -t
sudo tail -f /var/log/nginx/error.log
```

### **Αν τα routes δεν λειτουργούν:**
```bash
# Έλεγχος αν υπάρχει το index.html
ls -la /home/forge/sweat24.obs.com.gr/dist/index.html

# Έλεγχος permissions
sudo chown -R forge:forge /home/forge/sweat24.obs.com.gr/dist/
```

### **Rollback αν χρειαστεί:**
```bash
sudo cp /etc/nginx/sites-available/sweat24.obs.com.gr.backup /etc/nginx/sites-available/sweat24.obs.com.gr
sudo systemctl reload nginx
```

---

## 📁 **ΑΡΧΕΙΑ ΠΟΥ ΔΗΜΙΟΥΡΓΗΘΗΚΑΝ**

1. **`nginx-sweat24-spa-config.conf`** - Η νέα ρύθμιση
2. **`NGINX_SPA_FIX_INSTRUCTIONS.md`** - Αυτές οι οδηγίες

---

## 🎯 **ΑΠΟΤΕΛΕΣΜΑ**

Μετά την εφαρμογή:
- ✅ Τα URLs του React app θα λειτουργούν (π.χ. `/orders`, `/profile`)
- ✅ Το refresh στις σελίδες δεν θα δίνει 404 
- ✅ Τα static assets θα έχουν caching
- ✅ Το API proxy θα λειτουργεί για same-origin requests
- ✅ Όλες οι PHP ρυθμίσεις θα έχουν αφαιρεθεί

**Η React SPA θα λειτουργεί πλήρως!** 🚀 