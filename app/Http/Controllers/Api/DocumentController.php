<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\DocumentUseTrait;

class DocumentController extends Controller
{
    use DocumentUseTrait;
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
                switch ($sortBy) {
                    case 'name':
                        $data['sortedBy'] = 'name';
                        break;
                    case 'docType':
                        $data['sortedBy'] = 'type';
                        break;
                    case 'planTypes':
                        $data['sortedBy'] = 'plan_type';
                        break;
                    case 'file_extension':
                        $data['sortedBy'] = 'file_extension';
                        break;
                    case 'created_at':
                        $data['sortedBy'] = 'created_at';
                        break;
                    
                    default:
                    $data['sortedBy'] = 'name';
                        break;
                }
            }
            if ($UserData['is_user'] == 'General Contractor') {
                $mergedProject = $this->inviteMailSent($data['projectId'],$UserData['email']);
                if ($mergedProject) {
                    $data['projectId'] = $mergedProject['merged_project_id'];
                }
                if (empty($data['showFilesForGcComp'])) {
                    $gcForFile = $this->getGCForfile($data['projectId']);
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
            $result = $this->getsortedFiles($data, $UserData);
            if (!empty($result)) {
                $returnData = $result;
            } else {
                $error = config('apiconstant.DATA_NOT_FOUND');
            }
        } catch (\Exception $e) {
            $error = config('apiconstant.DATA_NOT_FOUND');
        }
        return $this->returnableResponseData($returnData, $error);
    }
}
