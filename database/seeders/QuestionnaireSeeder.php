<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionnaireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questionnaires = [
            [
                'title' => 'Εβδομαδιαία Αξιολόγηση',
                'description' => 'Πώς ήταν η εβδομάδα σας στο Sweat24;',
                'triggers' => ['weekly'],
                'frequency_settings' => [
                    'days' => ['friday'],
                    'time' => '18:00'
                ],
                'questions' => [
                    [
                        'type' => 'rating',
                        'question' => 'Πώς θα βαθμολογούσατε την εμπειρία σας αυτή την εβδομάδα;',
                        'description' => 'Σε κλίμακα 1-5, με 5 να είναι η καλύτερη δυνατή εμπειρία',
                        'required' => true,
                        'options' => ['1', '2', '3', '4', '5']
                    ],
                    [
                        'type' => 'text',
                        'question' => 'Τι σας άρεσε περισσότερο αυτή την εβδομάδα;',
                        'description' => 'Περιγράψτε λεπτομερώς τι σας έκανε να νιώθετε θετικά',
                        'required' => false
                    ],
                    [
                        'type' => 'text',
                        'question' => 'Τι θα μπορούσαμε να βελτιώσουμε;',
                        'description' => 'Μοιραστείτε τις προτάσεις σας για να γίνουμε καλύτεροι',
                        'required' => false
                    ],
                    [
                        'type' => 'rating_10',
                        'question' => 'Σε κλίμακα 1-10, πόσο ικανοποιημένος είστε συνολικά;',
                        'description' => 'Με 10 να είναι η μέγιστη ικανοποίηση και 1 η ελάχιστη',
                        'required' => true,
                        'options' => ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10']
                    ],
                    [
                        'type' => 'number',
                        'question' => 'Πόσα μαθήματα παρακολουθήσατε αυτή την εβδομάδα;',
                        'description' => 'Εισάγετε τον ακριβή αριθμό των μαθημάτων που παρακολουθήσατε',
                        'required' => true,
                        'min' => 0,
                        'max' => 20
                    ],
                    [
                        'type' => 'yes_no',
                        'question' => 'Θα μας προτείνατε σε φίλους;',
                        'description' => 'Η γνώμη σας είναι σημαντική για εμάς',
                        'required' => true
                    ]
                ],
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'title' => 'Αξιολόγηση Μαθήματος',
                'description' => 'Πώς ήταν το τελευταίο σας μάθημα;',
                'triggers' => ['after_lesson'],
                'frequency_settings' => null,
                'questions' => [
                    [
                        'type' => 'rating',
                        'question' => 'Πώς θα βαθμολογούσατε το μάθημα;',
                        'description' => 'Αξιολογήστε τη συνολική ποιότητα και δομή του μαθήματος',
                        'required' => true,
                        'options' => ['1', '2', '3', '4', '5']
                    ],
                    [
                        'type' => 'rating',
                        'question' => 'Πώς θα βαθμολογούσατε τον προπονητή;',
                        'description' => 'Βαθμολογήστε την καθοδήγηση και επικοινωνία του προπονητή',
                        'required' => true,
                        'options' => ['1', '2', '3', '4', '5']
                    ],
                    [
                        'type' => 'multiple_choice',
                        'question' => 'Τι σας άρεσε περισσότερο;',
                        'description' => 'Επιλέξτε όλα όσα ισχύουν για εσάς',
                        'required' => false,
                        'options' => [
                            'Η ένταση της προπόνησης',
                            'Η ποικιλία των ασκήσεων',
                            'Η καθοδήγηση του προπονητή',
                            'Το περιβάλλον',
                            'Άλλο'
                        ]
                    ],
                    [
                        'type' => 'text',
                        'question' => 'Πρόσθετα σχόλια ή προτάσεις;',
                        'description' => 'Μοιραστείτε οποιαδήποτε άλλη σκέψη ή πρόταση βελτίωσης',
                        'required' => false
                    ]
                ],
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'title' => 'Καθημερινή Ευεξία',
                'description' => 'Πώς νιώθετε σήμερα;',
                'triggers' => ['daily'],
                'frequency_settings' => [
                    'time' => '09:00'
                ],
                'questions' => [
                    [
                        'type' => 'rating',
                        'question' => 'Πώς είναι τα επίπεδα ενέργειάς σας σήμερα;',
                        'description' => 'Αξιολογήστε πόσο ενεργητικός και δραστήριος νιώθετε αυτή τη στιγμή',
                        'required' => true,
                        'options' => ['1', '2', '3', '4', '5']
                    ],
                    [
                        'type' => 'rating',
                        'question' => 'Πώς κοιμηθήκατε χτες;',
                        'description' => 'Πόσο ξεκούραστος νιώθετε από τον ύπνο σας χτες',
                        'required' => true,
                        'options' => ['1', '2', '3', '4', '5']
                    ],
                    [
                        'type' => 'yes_no',
                        'question' => 'Έχετε κάποια ενοχλήσεις ή πόνο;',
                        'description' => 'Αναφέρετε αν νιώθετε οποιοδήποτε σωματικό ενοχλητικό σύμπτωμα',
                        'required' => false
                    ],
                    [
                        'type' => 'text',
                        'question' => 'Σχόλια για τη σημερινή σας κατάσταση;',
                        'description' => 'Περιγράψτε οποιαδήποτε άλλη πληροφορία για τη φυσική ή ψυχική σας κατάσταση',
                        'required' => false
                    ]
                ],
                'is_active' => true,
                'created_by' => 1
            ]
        ];

        foreach ($questionnaires as $questionnaire) {
            \App\Models\Questionnaire::create($questionnaire);
        }
    }
}
