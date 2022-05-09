<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function getProjectDocumentsorted(Request $request)
    {
        try {
            $data = array();
            $inputData = $request->input();
            $UserData = $request->user;
            $returnData = null;
            $error = null;
            $data['pageNumber'] = isset($inputData['pageNumber']) ? (!empty($inputData['pageNumber']) ? $inputData['pageNumber'] : null) : null;
            $data['orderBy'] = isset($inputData['orderBy']) ? $inputData['orderBy'] : null;
            $data['showFilesForGcComp'] = isset($inputData['showFilesForGcComp']) ? $inputData['showFilesForGcComp'] : null;
            $data['limit'] = isset($inputData['limit']) ? $inputData['limit'] : 10;
            $data['skip'] = ($data['pageNumber'] - 1) * ($data['limit']);
            $data['projectId'] = isset($inputData['projectId']) ? $inputData['projectId'] : null;
            $data['planSubTypes'] = isset($inputData['planSubTypes']) ? $inputData['planSubTypes'] : null;
            if (!empty($inputData['sortedBy'])) {
                $sortBy = $inputData['sortedBy'];
                if ($sortBy == 'name') {
                    $data['sortedBy'] = 'name';
                } elseif ($sortBy == 'docType') {
                    $data['sortedBy'] = 'type';
                } elseif ($sortBy == 'planTypes') {
                    $data['sortedBy'] = 'plan_type';
                } elseif ($sortBy == 'file_extension') {
                    $data['sortedBy'] = 'file_extension';
                } elseif ($sortBy == 'created_at') {
                    $data['sortedBy'] = 'created_at';
                } else {
                    $data['sortedBy'] = 'name';
                }
            }
            if ($UserData['is_user'] == 'General Contractor') {
                $mergedProject = ProjectInvitationMailSent::where('project_id', $data['projectId'])
                    ->where('emailId', $UserData['email'])
                    ->Where(function ($subquery) {
                        $subquery->orwhere('is_merged', true)
                            ->orWhere('is_approved_merge', true);
                    })->first();
                if ($mergedProject) {
                    $data['projectId'] = $mergedProject['merged_project_id'];
                }
                if (empty($data['showFilesForGcComp'])) {
                    $gcForFile = ProjectInvitationMailSent::where('project_id', $data['projectId'])
                        ->where('gc_project_invitation_mail_sent.is_original_gc', true)
                        ->leftjoin('user_profile', 'user_profile.id_user', 'gc_project_invitation_mail_sent.user_id')
                        ->first();
                    if (!empty($gcForFile)) {
                        $data['showFilesForGcComp'] = $gcForFile['id_company'];
                    } else {
                        $data['showFilesForGcComp'] = "";
                    }
                }
            }
            $docType = isset($inputData['docType']) ? $inputData['docType'] : null;
            if (!empty($docType)) {
                $data['docType'] = $docType;
            }
            $getAllProjectObj = new ProjectFiles();
            $result = $getAllProjectObj->getsortedFiles($data, $UserData);
            if (!empty($result)) {
                $returnData = $result;
            } else {
                $error = ApiConstant::DATA_NOT_FOUND;
            }
        } catch (\Exception $e) {
            $error = ApiConstant::DATA_NOT_FOUND;
        }
        return $this->returnableResponseData($returnData, $error);
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
