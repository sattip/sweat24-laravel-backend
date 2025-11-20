# Οικονομικές Αναφορές API - Financial Reports

## Επισκόπηση
Το σύστημα Οικονομικών Αναφορών παρέχει λεπτομερή στατιστικά στοιχεία για την οικονομική απόδοση της επιχείρησης, συμπεριλαμβανομένης της ανάλυσης του customer journey από trial χρήστες μέχρι συνδρομητές.

## Ανάλυση Customer Journey

### Τύποι Πελατών
Το σύστημα παρακολουθεί τους πελάτες σε διάφορα στάδια:

1. **Trial Χρήστες** - Ξεκινούν με δωρεάν trial
2. **Πρώτη Αγορά** - Αγοράζουν το πρώτο τους πακέτο
3. **Ενεργοί Συνδρομητές** - Συνεχίζουν να αγοράζουν πακέτα
4. **Inactive/Churned** - Σταματούν να χρησιμοποιούν τις υπηρεσίες

### Μετρικές Μετατροπής
- **Trial to First Purchase**: Ποσοστό μετατροπής από trial σε πρώτη αγορά
- **First Purchase to Repeat**: Ποσοστό μετατροπής σε επαναλαμβανόμενο πελάτη
- **Average Time to Convert**: Μέσος χρόνος από trial μέχρι πρώτη αγορά
- **Customer Lifetime Value (LTV)**: Συνολική αξία πελάτη σε όλη τη διάρκεια

## Διαθέσιμα Endpoints

Όλα τα endpoints είναι προστατευμένα και απαιτούν authentication με admin ρόλο.

**Base URL:** `/api/v1/admin/financial-reports/`

### Customer Journey Analytics Endpoints

#### 13. Ανάλυση Μετατροπής Trial Χρηστών
```http
GET /api/v1/admin/financial-reports/customer-conversion
```

**Παράμετροι:**
- `start_date`, `end_date`, `store_id`

**Επιστρέφει:**
```json
{
  "period": {"start_date": "2024-01-01", "end_date": "2024-01-31"},
  "conversion_funnel": {
    "trial_users": 150,
    "first_purchase": 45,
    "repeat_customers": 32,
    "conversion_rate_trial_to_first": "30%",
    "conversion_rate_first_to_repeat": "71.1%"
  },
  "time_to_convert": {
    "average_days": 14.2,
    "median_days": 12,
    "fastest_conversion": 1,
    "slowest_conversion": 45
  },
  "conversion_sources": {
    "organic": 35,
    "referral": 25,
    "social_media": 20,
    "paid_ads": 15,
    "other": 5
  }
}
```

#### 14. Customer Lifetime Value (LTV) Analysis
```http
GET /api/v1/admin/financial-reports/customer-ltv
```

**Παράμετροι:**
- `start_date`, `end_date`, `store_id`
- `cohort_period` (optional): monthly, quarterly (default: monthly)

**Επιστρέφει:**
```json
{
  "period": {"cohort_period": "monthly"},
  "cohort_analysis": [
    {
      "cohort": "2024-01",
      "customers": 25,
      "cohort_value": 1500.00,
      "avg_customer_value": 60.00
    }
  ],
  "overall_ltv": {
    "avg_ltv": 75.50,
    "total_customers": 150,
    "total_value": 8500.00
  },
  "ltv_forecast": [
    {
      "cohort": "2024-01",
      "current_ltv": 60.00,
      "projected_ltv": 90.00,
      "customers": 25
    }
  ]
}
```

#### 15. Retention & Churn Analysis
```http
GET /api/v1/admin/financial-reports/retention-analysis
```

**Παράμετροι:**
- `periods` (optional): Αριθμός περιόδων για ανάλυση (default: 12)
- `store_id` (optional)

**Επιστρέφει:**
```json
{
  "periods": 12,
  "retention_rates": {
    "2024-01": 85.5,
    "2024-02": 82.3,
    "2024-03": 88.7
  },
  "churn_rates": {
    "2024-01": 14.5,
    "2024-02": 17.7,
    "2024-03": 11.3
  },
  "cohort_retention": [
    {
      "period": "2024-01",
      "active_customers": 120,
      "previous_active": 140,
      "retention_rate": 85.7,
      "churn_rate": 14.3
    }
  ]
}
```

### 1. Dashboard Σύνοψη
```http
GET /api/v1/admin/financial-reports/dashboard
```

**Παράμετροι:**
- `start_date` (optional): Ημερομηνία έναρξης (YYYY-MM-DD)
- `end_date` (optional): Ημερομηνία λήξης (YYYY-MM-DD)
- `store_id` (optional): ID καταστήματος

**Επιστρέφει:** Συνολική οικονομική σύνοψη που περιλαμβάνει:
```json
{
  "period": {...},
  "summary": {
    "total_revenue": 12500.00,
    "total_expenses": 3800.00,
    "net_profit": 8700.00,
    "profit_margin": "69.6%"
  },
  "customer_journey": {
    "new_trial_users": 45,
    "converted_to_paid": 12,
    "conversion_rate": "26.7%",
    "avg_conversion_time_days": 8
  },
  "revenue_by_category": [...],
  "top_customers": [...],
  "package_statistics": {...}
}
```

### 2. Συνολικά Έσοδα
```http
GET /api/v1/admin/financial-reports/total-revenue
```

**Παράμετροι:**
- `start_date`, `end_date`, `store_id`
- `group_by` (optional): day, week, month, year (default: month)

### 3. Έσοδα ανά Πελάτη
```http
GET /api/v1/admin/financial-reports/revenue-per-customer
```

**Παράμετροι:**
- `start_date`, `end_date`, `store_id`
- `limit` (optional): Αριθμός πελατών (default: 50)
- `sort_by` (optional): total_revenue, booking_count, avg_booking_value

**Επιστρέφει:** Λεπτομερή ανάλυση πελατών με στοιχεία customer journey:
```json
{
  "period": {...},
  "customers": [
    {
      "id": 123,
      "name": "Ιωάννης Παπαδόπουλος",
      "email": "john@example.com",
      "customer_type": "repeat_customer",
      "first_purchase_date": "2024-01-15",
      "last_purchase_date": "2024-02-20",
      "total_revenue": 450.00,
      "total_bookings": 8,
      "avg_booking_value": 56.25,
      "conversion_path": "trial → personal_training → group_classes",
      "days_since_first_purchase": 45,
      "purchase_frequency_days": 15
    }
  ]
}
```

### 4. Έσοδα ανά Υπηρεσία
```http
GET /api/v1/admin/financial-reports/revenue-per-service
```

Παρουσιάζει έσοδα από πακέτα και στατιστικά κρατήσεων ανά υπηρεσία.

### 5. Έσοδα ανά Κατάστημα
```http
GET /api/v1/admin/financial-reports/revenue-per-store
```

Συγκρίνει την απόδοση διαφορετικών καταστημάτων.

### 6. Κορυφαίοι Πελάτες
```http
GET /api/v1/admin/financial-reports/top-customers
```

**Παράμετροι:**
- `start_date`, `end_date`, `store_id`
- `limit` (optional): Αριθμός πελατών (default: 20)
- `metric` (optional): revenue, bookings, avg_value, ltv

**Επιστρέφει:** Κορυφαίοι πελάτες με λεπτομερή customer journey analytics:
```json
{
  "period": {...},
  "top_customers": [
    {
      "id": 456,
      "name": "Μαρία Κωνσταντίνου",
      "customer_status": "vip_customer",
      "lifetime_value": 2850.00,
      "total_revenue": 1250.00,
      "total_bookings": 25,
      "avg_booking_value": 50.00,
      "first_visit": "2023-08-15",
      "last_visit": "2024-02-20",
      "visit_frequency": "every_12_days",
      "preferred_services": ["EMS", "Personal Training"],
      "conversion_timeline": {
        "trial_start": "2023-08-10",
        "first_purchase": "2023-08-20",
        "became_regular": "2023-09-15"
      },
      "loyalty_tier": "Gold"
    }
  ]
}
```

### 7. Στατιστικά Πακέτων
```http
GET /api/v1/admin/financial-reports/package-statistics
```

Ανάλυση πωλήσεων πακέτων με έσοδα και δημοτικότητα.

### 8. Στατιστικά Προϊόντων
```http
GET /api/v1/admin/financial-reports/product-statistics
```

Ανάλυση πωλήσεων προϊόντων από το κατάστημα.

### 9. Ανάλυση Εξόδων
```http
GET /api/v1/admin/financial-reports/expense-analysis
```

Κατανομή εξόδων ανά κατηγορία.

### 10. Τάσεις Εσόδων
```http
GET /api/v1/admin/financial-reports/revenue-trends
```

**Παράμετροι:**
- `periods` (optional): Αριθμός μηνών προς ανάλυση (default: 12)

### 11. Ανάλυση Μεθόδων Πληρωμής
```http
GET /api/v1/admin/financial-reports/payment-methods
```

Στατιστικά χρήσης διαφορετικών μεθόδων πληρωμής.

### 12. Ανάλυση Κερδοφορίας
```http
GET /api/v1/admin/financial-reports/profitability-analysis
```

Λεπτομερής ανάλυση κερδοφορίας με περιθώρια κέρδους.

## Παραδείγματα Χρήσης

### Customer Journey Analytics

#### Ανάλυση Μετατροπής Trial Χρηστών
```bash
GET /api/v1/admin/financial-reports/customer-conversion?start_date=2024-01-01&end_date=2024-01-31
```

#### Customer Lifetime Value Analysis
```bash
GET /api/v1/admin/financial-reports/customer-ltv?cohort_period=monthly
```

#### Retention & Churn Analysis για 6 μήνες
```bash
GET /api/v1/admin/financial-reports/retention-analysis?periods=6
```

### Βασικές Οικονομικές Αναφορές

#### Αποτελέσματα Τρέχοντος Μήνα με Customer Journey Data
```bash
GET /api/v1/admin/financial-reports/dashboard?start_date=2024-01-01&end_date=2024-01-31
```

#### Κορυφαίοι 10 Πελάτες με βάση Lifetime Value
```bash
GET /api/v1/admin/financial-reports/top-customers?limit=10&metric=ltv&start_date=2024-01-01
```

#### Ανάλυση νέων πελατών που ξεκίνησαν από trial
```bash
GET /api/v1/admin/financial-reports/revenue-per-customer?sort_by=avg_booking_value&limit=20
```

#### Έσοδα ανά Κατάστημα για το Τρέχον Έτος
```bash
GET /api/v1/admin/financial-reports/revenue-per-store?start_date=2024-01-01&end_date=2024-12-31
```

#### Τάσεις Εσόδων για τους Τελευταίους 6 Μήνες
```bash
GET /api/v1/admin/financial-reports/revenue-trends?periods=6
```

### Σύνθετα Ερωτήματα

#### Σύγκριση μετατροπής ανά κατάστημα
```bash
GET /api/v1/admin/financial-reports/dashboard?store_id=1
# Συγκρίνετε με store_id=2 για να δείτε διαφορές μετατροπής
```

#### Ανάλυση αφοσίωσης πελατών (loyalty analysis)
```bash
GET /api/v1/admin/financial-reports/top-customers?metric=revenue&limit=50
# Ταξινόμηση βάσει συχνότητας αγορών και μέσης αξίας
```

## Δομή Απαντήσεων

Όλα τα endpoints επιστρέφουν δεδομένα σε σταθερή δομή:

```json
{
  "period": {
    "start_date": "2024-01-01",
    "end_date": "2024-01-31",
    "store_id": null
  },
  "data": {
    // Δεδομένα συγκεκριμένου endpoint
  }
}
```

## Σημαντικές Σημειώσεις

1. **Authentication:** Απαιτείται admin authentication
2. **Ημερομηνίες:** Χρησιμοποιήστε format YYYY-MM-DD
3. **Store Filtering:** Το `store_id` είναι προαιρετικό για φιλτράρισμα ανά κατάστημα
4. **Performance:** Τα endpoints είναι βελτιστοποιημένα για μεγάλα datasets
5. **Currency:** Όλα τα χρηματικά ποσά είναι σε ευρώ (€)

## Διαθέσιμες Μετρικές

### Οικονομικές Μετρικές
- **Έσοδα:** Συνολικά έσοδα από πακέτα, προϊόντα και υπηρεσίες
- **Έξοδα:** Κατανομή εξόδων ανά κατηγορία
- **Κέρδος:** Καθαρό κέρδος και περιθώρια κέρδους
- **Τάσεις:** Ιστορική ανάλυση και προβλέψεις

### Μετρικές Customer Journey
- **Trial Conversion Rate:** Ποσοστό μετατροπής από trial σε πληρωμένους χρήστες
- **Time to Convert:** Μέσος χρόνος από trial μέχρι πρώτη αγορά
- **Customer Lifetime Value (LTV):** Συνολική αξία πελάτη
- **Retention Rate:** Ποσοστό διατήρησης πελατών
- **Churn Rate:** Ποσοστό απώλειας πελατών
- **Purchase Frequency:** Συχνότητα αγορών ανά πελάτης
- **Average Order Value (AOV):** Μέση αξία παραγγελίας

### Μετρικές Επιδόσεων
- **Πελάτες:** Ανάλυση συμπεριφοράς και αξίας πελατών
- **Υπηρεσίες:** Δημοτικότητα και έσοδα ανά υπηρεσία
- **Προϊόντα:** Πωλήσεις και απόδοση προϊόντων
- **Καταστήματα:** Σύγκριση απόδοσης ανά τοποθεσία

## Customer Journey Insights

### Τύποι Μετατροπής
1. **Trial → First Purchase:** Από δωρεάν trial σε πρώτη πληρωμένη υπηρεσία
2. **First Purchase → Repeat Customer:** Από πρώτη αγορά σε επαναλαμβανόμενο πελάτη
3. **Service Upgrade:** Αναβάθμιση σε υψηλότερης αξίας υπηρεσίες
4. **Cross-sell:** Πρόσθετες υπηρεσίες ή προϊόντα

### Κλειδιά Μετρικές Επιτυχίας
- **Conversion Funnel Efficiency:** Πόσο αποτελεσματικά μετατρέπονται οι trial χρήστες
- **Customer Acquisition Cost (CAC):** Κόστος απόκτησης νέου πελάτη
- **Customer Retention Cost:** Κόστος διατήρησης υφιστάμενων πελατών
- **Net Promoter Score (NPS):** Ικανοποίηση και πιθανότητα σύστασης

### Πρακτικές Χρήσεις

#### Εντοπίστε προβλήματα μετατροπής
```bash
# Δείτε ποιοι trial χρήστες δεν μετατρέπονται
GET /api/v1/admin/financial-reports/customer-conversion?start_date=2024-01-01&end_date=2024-01-31
```

#### Βρείτε τους πιο πολύτιμους πελάτες
```bash
# Ταξινόμηση βάσει LTV για στοχευμένο marketing
GET /api/v1/admin/financial-reports/top-customers?metric=ltv&limit=20
```

#### Αναλύστε retention trends
```bash
# Δείτε πώς αλλάζει η διατήρηση πελατών με τον χρόνο
GET /api/v1/admin/financial-reports/retention-analysis?periods=12
```
