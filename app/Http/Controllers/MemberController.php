<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('membership_type', '!=', 'Admin')
                    ->orWhereNull('membership_type')
                    ->with(['packages' => function($q) {
                        $q->where('status', 'active');
                    }]);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        // EMS filtering
        if ($request->has('ems_filter') && $request->ems_filter) {
            switch ($request->ems_filter) {
                case 'interested':
                    $query->where('ems_interest', true);
                    break;
                case 'interested_no_contraindications':
                    $query->where('ems_interest', true)
                          ->where(function($q) {
                              $q->whereNull('ems_contraindications')
                                ->orWhereJsonLength('ems_contraindications', 0);
                          });
                    break;
                case 'interested_with_contraindications':
                    $query->where('ems_interest', true)
                          ->whereNotNull('ems_contraindications')
                          ->whereJsonLength('ems_contraindications', '>', 0);
                    break;
                case 'not_interested':
                    $query->where(function($q) {
                        $q->where('ems_interest', false)
                          ->orWhereNull('ems_interest');
                    });
                    break;
            }
        }
        
        $members = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('admin.members.index', compact('members'));
    }
    
    /**
     * Export members with EMS data to CSV
     */
    public function exportEmsData(Request $request)
    {
        $query = User::where('membership_type', '!=', 'Admin')
                    ->orWhereNull('membership_type');
        
        // Apply same filters as index
        if ($request->has('ems_filter') && $request->ems_filter) {
            switch ($request->ems_filter) {
                case 'interested':
                    $query->where('ems_interest', true);
                    break;
                case 'interested_no_contraindications':
                    $query->where('ems_interest', true)
                          ->where(function($q) {
                              $q->whereNull('ems_contraindications')
                                ->orWhereJsonLength('ems_contraindications', 0);
                          });
                    break;
                case 'interested_with_contraindications':
                    $query->where('ems_interest', true)
                          ->whereNotNull('ems_contraindications')
                          ->whereJsonLength('ems_contraindications', '>', 0);
                    break;
                case 'not_interested':
                    $query->where(function($q) {
                        $q->where('ems_interest', false)
                          ->orWhereNull('ems_interest');
                    });
                    break;
            }
        }
        
        $members = $query->get();
        
        // Generate CSV
        $filename = 'ems_data_export_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($members) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID',
                'Name',
                'Email',
                'Phone',
                'EMS Interest',
                'EMS Contraindications',
                'EMS Liability Accepted',
                'Emergency Contact',
                'Emergency Phone',
                'Join Date'
            ]);
            
            foreach ($members as $member) {
                $contraindications = '';
                if ($member->ems_contraindications && is_array($member->ems_contraindications)) {
                    $contraindicationsList = [];
                    foreach ($member->ems_contraindications as $name => $data) {
                        if (isset($data['has_condition']) && $data['has_condition']) {
                            $item = $name;
                            if (isset($data['year_of_onset']) && $data['year_of_onset']) {
                                $item .= ' (' . $data['year_of_onset'] . ')';
                            }
                            $contraindicationsList[] = $item;
                        }
                    }
                    $contraindications = implode('; ', $contraindicationsList);
                }
                
                fputcsv($file, [
                    $member->id,
                    $member->name,
                    $member->email,
                    $member->phone ?? '',
                    $member->ems_interest ? 'Yes' : 'No',
                    $contraindications,
                    $member->ems_liability_accepted ? 'Yes' : 'No',
                    $member->emergency_contact ?? '',
                    $member->emergency_phone ?? '',
                    $member->created_at->format('Y-m-d')
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Generate EMS statistics report
     */
    public function emsStatistics()
    {
        $stats = [
            'total_members' => User::where('membership_type', '!=', 'Admin')->count(),
            'ems_interested' => User::where('ems_interest', true)->count(),
            'ems_no_contraindications' => User::where('ems_interest', true)
                ->where(function($q) {
                    $q->whereNull('ems_contraindications')
                      ->orWhereJsonLength('ems_contraindications', 0);
                })->count(),
            'ems_with_contraindications' => User::where('ems_interest', true)
                ->whereNotNull('ems_contraindications')
                ->whereJsonLength('ems_contraindications', '>', 0)
                ->count(),
            'ems_liability_accepted' => User::where('ems_liability_accepted', true)->count(),
        ];
        
        // Get most common contraindications
        $members = User::where('ems_interest', true)
            ->whereNotNull('ems_contraindications')
            ->get();
        
        $contraindicationsCount = [];
        foreach ($members as $member) {
            if ($member->ems_contraindications && is_array($member->ems_contraindications)) {
                foreach ($member->ems_contraindications as $name => $data) {
                    if (isset($data['has_condition']) && $data['has_condition']) {
                        if (!isset($contraindicationsCount[$name])) {
                            $contraindicationsCount[$name] = 0;
                        }
                        $contraindicationsCount[$name]++;
                    }
                }
            }
        }
        
        arsort($contraindicationsCount);
        $stats['common_contraindications'] = array_slice($contraindicationsCount, 0, 10, true);
        
        return response()->json($stats);
    }
}