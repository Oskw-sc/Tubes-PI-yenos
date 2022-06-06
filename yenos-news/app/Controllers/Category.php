<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;
use \Firebase\JWT\JWT;

use \Firebase\JWT\Key;

class Category extends ResourceController
{

    function __construct()
    {
        $this->categorymodel = new CategoryModel();
    }

    // public function auth()
    // {
    //     $key = getenv('JWT_SECRET');
    //     $authHeader = $this->request->getHeader("Authorization");
    //     if(!$authHeader) return $this->failUnauthorized('auth-token must be passed as header request');
    //     return $authHeader->getValue();
    // }

    public function index()
    {   
        try {
            $data = $this->categorymodel->orderBy('name', 'ASC')->findAll();
            if (count($data) > 0) {
                $response = [
                    'status' => 200,
                    'error' => false,
                    'message' => 'Retrieve list succeed',
                ];
            } else {
                $response = [
                    'status' => 404,
                    'error' => false,
                    'message' => 'List of category is empty',
                ];
            }
        } catch (Exception $ex) {
            $response = [
                'status' => 500,
                'error' => true,
                'message' => 'Internal server error, please try again later',
            ];
        }
        return $this->respond($response, $response['status']);
    }   
 
    public function show($id = null)
    {
        $data = $this->categorymodel->where('id', $id)->findAll();

        if ($data) {
            return $this->respond($data, 200);
        } else {
            return $this->failNotFound("Cannot found category by id : $id");
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

                    $data = [
                        "name" => $this->request->getVar("name"),
                    ];

                    if ($this->categorymodel->insert($data)) {
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
        $key = getenv('JWT_SECRET');
        $authHeader = $this->request->getHeader("Authorization");
        if (!$authHeader) return $this->failUnauthorized('auth-token must be passed as header request');
        $token = $authHeader->getValue();
        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            if ($decoded && ($decoded->exp - time() > 0)) {
                $iat = time(); // current timestamp value
                $data = $this->request->getRawInput(); //get all data from input
                $data['id'] = $id;
                $dataExist = $this->categorymodel->where('id', $id)->findAll();
                if (!$dataExist) {
                    return $this->failNotFound("Cannot found category by id : $id");
                }

                $rules = [
                    "name" => "required|is_unique[categories.name]",
                ];

                $messages = [
                    "name" => [
                        "required" => "name is required"
                    ],
                ];

                if (!$this->validate($rules, $messages)) {
                    $response = [
                        'status' => 500,
                        'error' => true,
                        'message' => $this->validator->getErrors(),
                        'data' => []
                    ];
                    return $this->respond($response);
                }

                if($this->categorymodel->update($id, $data)) {
                    $response = [
                        'status'   => 200,
                        'messages' => [
                            'success' => 'Successfully update data by id : $id'
                        ]
                    ];
                } else {
                    $response = [
                        'status' => 500,
                        'message' => "Internal Server Error'",
                        'data' => []
                    ];
                };
                return $this->respond($response);
            }
        } catch (Exception $ex) {
            $response = [
                'status' => 401,
                'messages' => 'auth-token is invalid, might be expired',
            ];
            return $this->respondCreated($response);
        }
    }

    public function delete($id = null)
    {
        $key = getenv('JWT_SECRET');
        $authHeader = $this->request->getHeader("Authorization");
        if (!$authHeader) return $this->failUnauthorized('auth-token must be passed as header request');
        $token = $authHeader->getValue();
        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            if ($decoded && ($decoded->exp - time() > 0)) {
                $iat = time(); // current timestamp value

                $data = $this->categorymodel->where('id', $id)->findAll();

                if ($data) {
                    $this->categorymodel->delete($id);
                    $response = [
                        'status' => 200,
                        'error' => null,
                        'messages' => [
                            'success' => "Successfully delete data by id : $id",
                        ]
                    ];

                    return $this->respondDeleted($response);
                } else {
                    return $this->failNotFound("Cannot find data by id : $id");
                }
            }
        } catch (Exception $ex) {
            $response = [
                'status' => 401,
                'messages' => 'auth-token is invalid, might be expired',
            ];
            return $this->respondCreated($response);
        }
    }
}
