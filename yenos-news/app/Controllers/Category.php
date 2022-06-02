<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;
use \Firebase\JWT\JWT;

use \Firebase\JWT\Key;

class Category extends ResourceController
{
    // use ResponseTrait;
    // get all category
    public function index()
    {
        $model = new CategoryModel();
        $data = $model->findAll();
        return $this->respond($data, 200);
    }
 
    // get single category
    public function show($id = null)
    {
        $model = new CategoryModel();
        $data = $model->getWhere(['id' => $id])->getResult();
        if($data){
            return $this->respond($data);
        }else{
            return $this->failNotFound('No Data Found with id '.$id);
        }
    }

    public function create()
    {
        $key = getenv('JWT_SECRET');
        $authHeader = $this->request->getHeader("Authorization");
        if(!$authHeader) return $this->failUnauthorized('auth-token must be passed as header request');
        $token = $authHeader->getValue();

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $level = $decoded->data->acc_level;
            if($level != "admin") return $this->failForbidden('You dont have a permission to add category');

            if ($decoded && ($decoded->exp - time() > 0)) {

                $rules = [
                    "name" => "required|max_length[255]",
                ];

                $messages = [
                    "name" => [
                        "required" => "category is required"
                    ],
                ];

                if (!$this->validate($rules, $messages)) {
                    $response = [
                        'status' => 500,
                        'error' => true,
                        'message' => $this->validator->getErrors(),
                        'data' => []
                    ];
                } else {
                    $model = new CategoryModel();

                    $data = [
                        "name" => $this->request->getVar("name"),
                    ];

                    if ($model->insert($data)) {
                        $response = [
                            'status' => 201,
                            "error" => false,
                            'messages' => 'Category has been added successfully',
                            'data' => []
                        ];
                    } else {
                        $response = [
                            'status' => 500,
                            "error" => true,
                            'messages' => 'Failed to create Category',
                            'data' => []
                        ];
                    }
                }

                return $this->respondCreated($response);
            }
        } catch (Exception $ex) {
            $response = [
                'status' => 401,
                'messages' => 'auth-token is invalid, might be expired',
            ];
            return $this->respondCreated($response);
        }
    }

    public function update($id = null)
    {
        $model = new CategoryModel();
        $id = $this->request->getVar('id');
        $data = [
            'name' => $this->request->getVar('name')
        ];
        $model->update($id, $data);
        $response = [
            'status'   => 200,
            'error'    => $id,
            'messages' => [
                'success' => 'Category berhasil diubah.'
            ],
            'data' => [
                'profile' => $this->request
            ]
        ];
        return $this->respond($response);
    }

    public function delete($id = null)
    {
        $model = new CategoryModel();
        $data = $model->find($id);
        if($data){
            $model->delete($id);
            $response = [
                'status'   => 200,
                'messages' => [
                    'success' => 'Data Deleted'
                ]
            ];
             
            return $this->respondDeleted($response);
        }else{
            return $this->failNotFound('No Data Found with id '.$id);
        }
         
    }
}
