<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\CategoryModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;
use \Firebase\JWT\JWT;

use \Firebase\JWT\Key;

class Article extends ResourceController
{

    function __construct()
    {
        $this->model = new ArticleModel();
    }

    public function index()
    {
        $data = $this->model->orderBy('id', 'asc')->findAll();
        return $this->respond($data, 200);
    }

    public function show($id = null)
    {
        $data = $this->model->where('id', $id)->findAll();

        if ($data) {
            return $this->respond($data, 200);
        } else {
            return $this->failNotFound("Cannot found article by id : $id");
        }
    }
    public function create()
    {
        $key = getenv('JWT_SECRET');
        $authHeader = $this->request->getHeader("Authorization");
        if (!$authHeader) return $this->failUnauthorized('auth-token must be passed as header request');
        $token = $authHeader->getValue();
        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            if ($decoded && ($decoded->exp - time() > 0)) {
                $iat = time(); // current timestamp value

                $rules = [
                    "title" => "required|max_length[300]",
                    "cover" => "required|max_length[300]|valid_url",
                    "description" => "required",
                    "id_category" => "required", // Validasi Exist Id category
                ];

                $messages = [
                    "title" => [
                        "required" => "Title is required"
                    ],
                    "cover" => [
                        "required" => "Cover is required"
                    ],
                    "description" => [
                        "required" => "Description is required"
                    ],
                    "id_category" => [
                        "required" => "Id Category is required"
                    ]
                ];

                if (!$this->validate($rules, $messages)) {
                    $response = [
                        'status' => 500,
                        'error' => true,
                        'message' => $this->validator->getErrors(),
                        'data' => []
                    ];
                } else {
                    $this->CategoryModel = new CategoryModel();

                    $id_category = $this->request->getVar("id_category");
                    $is_exist = $this->CategoryModel->where('id', $id_category)->findAll();
                    if (!$is_exist) {
                        return $this->failNotFound("Category not found by id : $id_category");;
                    } else {

                        $data = [
                            "id_account" => $decoded->data->acc_id,
                            "id_category" => $this->request->getVar("id_category"),
                            "title" => $this->request->getVar("title"),
                            "cover" => $this->request->getVar("cover"),
                            "description" => $this->request->getVar("description"),
                            "status" => "active"
                        ];

                        if ($this->model->insert($data)) {
                            $response = [
                                'code' => 201,
                                'messages' => 'Article created',
                            ];
                        } else {
                            $response = [
                                'status' => 500,
                                'messages' => 'Internal Server Error',
                            ];
                        }
                    }
                }
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
                switch ($this->request->getMethod()) {
                    case 'put':
                        $rules = [
                            "title" => "required|max_length[300]",
                            "cover" => "required|max_length[300]",
                            "description" => "required",
                            "id_category" => "required",
                            "status" => "required",
                        ];

                        $messages = [
                            "title" => [
                                "required" => "Title is required"
                            ],
                            "cover" => [
                                "required" => "Cover is required"
                            ],
                            "description" => [
                                "required" => "Description is required"
                            ],
                            "id_category" => [
                                "required" => "Id Category is required"
                            ],
                            "status" => [
                                "required" => "Status is required",
                            ],
                        ];

                        $data_in = [
                            "id_account" => $decoded->data->acc_id,
                        ];

                        $data['id'] = $id;
                        $dataExist = $this->model->where('id', $id)->findAll();
                        if (!$dataExist) {
                            return $this->failNotFound("Cannot found article by id : $id");
                        }

                        if (!$this->validate($rules, $messages)) {
                            $response = [
                                'status' => 500,
                                'message' => $this->validator->getErrors()
                            ];
                            return $this->respond($response);
                        }

                        $this->CategoryModel = new CategoryModel();
                        $id_category = $data['id_category'];

                        $Category_isexist = $this->CategoryModel->where('id', $id_category)->findAll();
                        if (!$Category_isexist) {
                            return $this->failNotFound("Cannot found category by id: $id_category");;
                        }

                        $status = $data['status']; //mengambil inputan untuk status
                        if ($status == "active" or $status == "non-active") {

                            $this->model->update($id, $data); //input all data except id_account
                            $this->model->update($id, $data_in); //input only id_accout

                            $response = [
                                'status' => 200,
                                'messages' => [
                                    'success' => "Successfully update data by id : $id",
                                ]
                            ];
                            return $this->respond($response);
                        } else {
                            $response = [
                                'status' => 406,
                                'message' => "Status can only be 'active' or 'non-active'",
                            ];
                            return $this->respond($response);
                        }
                        break;

                    case 'patch':
                        $data = $this->request->getRawInput();
                        $data['id'] = $id;
                        $data_in = [
                            "id_account" => $decoded->data->acc_id,
                        ];
                        $dataExist = $this->model->where('id', $id)->findAll();
                        if (!$dataExist) {
                            return $this->failNotFound("Cannot found article by id : $id");
                        } 
                        if (isset($data['title'])) $this->model->set('title', $data['title']);
                        if (isset($data['cover'])) $this->model->set('cover', $data['cover']);
                        if (isset($data['description'])) $this->model->set('description', $data['description']);
                        if (isset($data['id_category'])){
                            $this->CategoryModel = new CategoryModel(); // Cek jika ada inputan category
                            $id_category = $data['id_category'];
                            $Category_isexist = $this->CategoryModel->where('id', $id_category)->findAll();
                            if (!$Category_isexist) {
                                return $this->failNotFound("Cannot found category by id: $id_category");
                            } else {
                            $this->model->set('id_category', $data['id_category']);
                            }
                        }

                        if (isset($data['status'])){ 
                        $status = $data['status']; //mengambil inputan untuk status
                            if ($status == "active" or $status == "non-active") {
                                $this->model->set('status', $data['status']);
                            } else {
                                $response = [
                                    'status' => 406,
                                    'error' => true,
                                    'message' => "Status can only be 'active' or 'non-active'",
                                ];
                                return $this->respond($response);
                            }
                        }
                        if ($this->model->update($id, $data) && $this->model->update($id, $data_in) ) {
                            return $this->respond([
                              'message' => "Successfully update data by id : $id",
                            ], 200, 'OK');
                          } else {
                            return $this->respond([
                              'message' => 'Something went wrong while updating, please try again later',
                            ], 500, 'Internal Server Error');
                          }    

                    break;
                    default:
                        return $this->respond([
                            'message' => 'This kind of method request is not accepted',
                            'method' => strtoupper($this->request->getMethod()),
                        ], 405, 'Method Not Allowed');
                };
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

                $data = $this->model->where('id', $id)->findAll();

                if ($data) {
                    $this->model->delete($id);
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
