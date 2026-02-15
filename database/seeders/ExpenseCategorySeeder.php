<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Ενοίκιο & Πάγια Έξοδα Χώρου',
                'description' => 'Ενοίκιο και πάγια έξοδα εγκαταστάσεων',
                'subcategories' => [
                    'Ενοίκιο εγκαταστάσεων',
                    'Κοινόχρηστα',
                    'ΔΕΗ (ηλεκτρικό ρεύμα)',
                    'Ύδρευση',
                    'Τηλεπικοινωνίες / Internet',
                    'Ασφάλεια κτιρίου (σύστημα συναγερμού, κάμερες κ.λπ.)',
                ]
            ],
            [
                'name' => 'Ανθρώπινο Δυναμικό',
                'description' => 'Έξοδα σχετικά με το προσωπικό',
                'subcategories' => [
                    'Μισθοδοσία προσωπικού (γυμναστές, γραμματεία, καθαριότητα κ.λπ.)',
                    'Εισφορές ΕΦΚΑ',
                    'Εκπαίδευση/Σεμινάρια προσωπικού',
                    'Bonus/Κίνητρα απόδοσης',
                ]
            ],
            [
                'name' => 'Εξοπλισμός Γυμναστηρίου',
                'description' => 'Αγορά και συντήρηση εξοπλισμού γυμναστηρίου',
                'subcategories' => [
                    'Αγορά οργάνων & μηχανημάτων',
                    'Συντήρηση & επισκευές εξοπλισμού',
                    'Αναλώσιμα (λάδια, καθαριστικά για τα μηχανήματα)',
                ]
            ],
            [
                'name' => 'Καθαριότητα & Υγιεινή',
                'description' => 'Έξοδα καθαριότητας και υγιεινής',
                'subcategories' => [
                    'Καθαριστικά προϊόντα',
                    'Χαρτικά (χαρτί υγείας, πετσέτες, σαπούνια)',
                    'Αμοιβή συνεργείων καθαρισμού (αν είναι εξωτερική συνεργασία)',
                ]
            ],
            [
                'name' => 'Μάρκετινγκ & Διαφήμιση',
                'description' => 'Έξοδα μάρκετινγκ και διαφήμισης',
                'subcategories' => [
                    'Social media (ads, διαχείριση σελίδων)',
                    'Εκτυπώσεις (φυλλάδια, banners)',
                    'Ιστοσελίδα (σχεδίαση, φιλοξενία, ανανέωση περιεχομένου)',
                    'Συμμετοχή σε εκθέσεις/εκδηλώσεις',
                ]
            ],
            [
                'name' => 'Διοικητικά & Λειτουργικά Έξοδα',
                'description' => 'Διοικητικά και λειτουργικά έξοδα',
                'subcategories' => [
                    'Λογιστικές υπηρεσίες',
                    'Λογισμικό CRM/ERP/Σύστημα συνδρομών',
                    'Υλικά γραφείου',
                    'Τραπεζικά έξοδα (POS, προμήθειες τραπεζών)',
                ]
            ],
            [
                'name' => 'Άδειες & Ασφάλειες',
                'description' => 'Άδειες λειτουργίας και ασφάλειες',
                'subcategories' => [
                    'Άδειες λειτουργίας',
                    'Ασφάλειες επαγγελματικής ευθύνης',
                    'Ασφάλιση εξοπλισμού / κτιρίου',
                ]
            ],
            [
                'name' => 'Εκδηλώσεις & Παροχές Μελών',
                'description' => 'Έξοδα εκδηλώσεων και παροχών μελών',
                'subcategories' => [
                    'Έπαθλα, δώρα για διαγωνισμούς',
                    'Πακέτα καλωσορίσματος (t-shirts, τσάντες κ.λπ.)',
                    'Δωρεάν μαθήματα/εκδηλώσεις για προσέλκυση νέων μελών',
                ]
            ],
            [
                'name' => 'Αποσβέσεις Παγίων',
                'description' => 'Αποσβέσεις παγίων στοιχείων',
                'subcategories' => [
                    'Απόσβεση εξοπλισμού και εγκαταστάσεων (λογιστικό έξοδο)',
                ]
            ],
            [
                'name' => 'Έκτακτα Έξοδα',
                'description' => 'Έκτακτα και απρόβλεπτα έξοδα',
                'subcategories' => [
                    'Βλάβες / ζημιές',
                    'Νομικά έξοδα',
                    'Αντικαταστάσεις εξοπλισμού λόγω φθοράς ή ατυχήματος',
                ]
            ],
        ];

        foreach ($categories as $index => $categoryData) {
            $mainCategory = ExpenseCategory::create([
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
                'category_type' => 'main',
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);

            foreach ($categoryData['subcategories'] as $subIndex => $subcategoryName) {
                ExpenseCategory::create([
                    'name' => $subcategoryName,
                    'category_type' => 'subcategory',
                    'parent_id' => $mainCategory->id,
                    'is_active' => true,
                    'sort_order' => $subIndex + 1,
                ]);
            }
        }
    }
}






