<?php

namespace App\Traits;

use App\Models\ProjectInvitationMailSent;
use App\Models\ProjectFiles;

trait DocumentUseTrait {
    public function index() {
        // Fetch all the users from the 'users' table.
        $users = User::all();
        return view('users')->with(compact('users'));
    }

    public function inviteMailSent($projectId, $email){
        $merged = ProjectInvitationMailSent::where('project_id', $projectId)
                    ->where('emailId', $email)
                    ->Where(function ($subquery) {
                        $subquery->orwhere('is_merged', true)
                            ->orWhere('is_approved_merge', true);
                    })->first();
        return $merged;

    }

    public function getGCForfile($projectId){
        $gcFile = ProjectInvitationMailSent::where('project_id', $projectId)
                        ->where('gc_project_invitation_mail_sent.is_original_gc', true)
                        ->leftjoin('user_profile', 'user_profile.id_user', 'gc_project_invitation_mail_sent.user_id')
                        ->first();
        return $gcFile;

    }

    public function getsortedFiles($data, $UserData)
    {
        $count = $count_plans = $count_specs = $count_general = $count_addendums = $count_RFI_response = 0;
        $docType = $data['docType'] ?? null;
        $planSubTypes = $data['planSubTypes'] ?? null;
        if ($UserData['is_user'] == 'General Contractor') {
            $result = ProjectFiles::where("id_project", $data['projectId'])
                ->where(function ($sub_query) use ($UserData, $docType, $planSubTypes) {
                    $sub_query->orwhere('gc_company_id', '9999999')
                        ->orWhere('gc_company_id', $UserData['id_company']);
                    if ($docType) {
                        $sub_query->where('type', $docType);
                    }
                    if ($planSubTypes) {
                        $sub_query->where('plan_type', $planSubTypes);
                    }
                })
                ->groupBy(['name', 'type'])
                ->orderBy($data['sortedBy'], $data['orderBy']);
        } else {
            $result = ProjectFiles::where(function ($sub_query) use ($data, $docType, $planSubTypes) {
                if ($data['showFilesForGcComp']) {
                    $sub_query->where('gc_company_id', $data['showFilesForGcComp'])
                        ->where('id_project', $data['projectId']);
                } else {
                    $all_projects = ProjectInvitationMailSent::where('project_id', $data['projectId'])->get()
                        ->pluck('merged_project_id')
                        ->unique()
                        ->values()
                        ->toArray();
                    $all_projects[] = $data['projectId'];
                    $sub_query->whereIn('id_project', array_filter($all_projects));
                }
                if ($docType) {
                    $sub_query->where('type', $docType);
                }
                if ($planSubTypes) {
                    $sub_query->where('plan_type', $planSubTypes);
                }
            })
                ->groupBy(['name', 'type'])
                ->orderBy($data['sortedBy'], $data['orderBy']);
        }
        $all_data_count = $result->get();
        $all_data = $result->offset($data['skip'])
            ->limit($data['limit'])
            ->get();


        if (!empty($all_data_count)) {
            foreach ($all_data_count as $single) {
                $count++;
                if ($single['type'] == "plans") {
                    $count_plans++;
                } elseif ($single['type'] == "specs") {
                    $count_specs++;
                } elseif ($single['type'] == "general") {
                    $count_general++;
                } elseif ($single['type'] == "addendums") {
                    $count_addendums++;
                } elseif ($single['type'] == "RFI_response") {
                    $count_RFI_response++;
                }
            }
        }
        if (!empty($all_data)) {
            $all_files = array();
            foreach ($all_data as $single) {
                $allData['docType'] = $single['type'];
                $allData['planTypes'] = empty($single['plan_type']) ? 'All Other Drawings' : $single['plan_type'];
                $allData['name'] = $single['name'];
                $allData['fullPath'] = $single['file_path'];
                $allData['projectId'] = $single['id_project'];
                $allData['docId'] = $single['id'];
                $allData['file_extension'] = $single['file_extension'];
                $allData['created_at'] = $single['created_at'];
                $all_files[] = $allData;
            }
        }
        return array(
            'message' => ApiConstant::DATA_FOUND, "data" => $all_files,
            'allcount' => $count,
            'countplans' => $count_plans,
            'countspecs' => $count_specs,
            'countgeneral' => $count_general,
            'countaddendums' => $count_addendums,
            'countRFI_response' => $count_RFI_response,
        );
    }
}