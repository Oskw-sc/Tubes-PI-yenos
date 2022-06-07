<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\CommentModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;
use \Firebase\JWT\JWT;

use \Firebase\JWT\Key;

class Article extends ResourceController
{

    function __construct()
    {
        $this->articleModel = new ArticleModel();
        $this->commentModel = new CommentModel();
    }

    public function index()
    {
        $data = $this->articleModel->orderBy('id', 'asc')->findAll();
        return $this->respond($data, 200);
    }

    public function show($id = null)
    {
        $data = $this->articleModel->where('id', $id)->findAll();

        if (!$data) {
            return $this->failNotFound("Cannot found article by id : $id");
        }

        $data_array = $this->articleModel->where('id', $id)->first();
        $id_account = $data_array['id_account'];
        $comment = $this->commentModel->where('id_article', $id)->findAll();
        $comment_count = count($comment);

        // var_dump($data_array);
        // var_dump($id_account);
        // var_dump($comment);

        $detail_data = [
            "id" => $id,
            "id_account" => $id_account,
            "id_category" => $data_array['id_category'],
            "title" => $data_array['title'],
            "cover" => $data_array['cover'],
            "description" => $data_array['description'],
            "datetime_added" => $data_array['datetime_added'],
            "datetime_updated" => $data_array['datetime_updated'],
            "status" => $data_array['status'],
            "comment_count" => $comment_count,
            "comment" => $comment,
        ];
        return $this->respond($detail_data, 200);
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

                        if ($this->articleModel->insert($data)) {
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
                        $dataExist = $this->articleModel->where('id', $id)->findAll();
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

                            $this->articleModel->update($id, $data); //input all data except id_account
                            $this->articleModel->update($id, $data_in); //input only id_accout

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
                        $dataExist = $this->articleModel->where('id', $id)->findAll();
                        if (!$dataExist) {
                            return $this->failNotFound("Cannot found article by id : $id");
                        }
                        if (isset($data['title'])) {

                            if ($data['title'] == "") {
                                return $this->failForbidden("title input cannot be empty");
                            }

                            $this->articleModel->set('title', $data['title']);
                        }

                        if (isset($data['cover'])) {

                            if ($data['cover'] == "") {
                                return $this->failForbidden("cover input cannot be empty");
                            }

                            $this->articleModel->set('cover', $data['cover']);
                        }
                        if (isset($data['description'])) {

                            if ($data['description'] == "") {
                                return $this->failForbidden("description input cannot be empty");
                            }

                            $this->articleModel->set('description', $data['description']);
                        }
                        if (isset($data['id_category'])) {

                            $this->CategoryModel = new CategoryModel(); // Cek jika ada inputan category

                            if ($data['id_category'] == "") {
                                return $this->failForbidden("id_category input cannot be empty");
                            }

                            $id_category = $data['id_category'];
                            $Category_isexist = $this->CategoryModel->where('id', $id_category)->findAll();

                            if (!$Category_isexist) {
                                return $this->failNotFound("Cannot found category by id: $id_category");
                            } else {
                                $this->articleModel->set('id_category', $data['id_category']);
                            }
                        }

                        if (isset($data['status'])) {

                            if ($data['status'] == "") {
                                return $this->failForbidden("status input cannot be empty");
                            }

                            $status = $data['status']; //mengambil inputan untuk status

                            if ($status == "active" or $status == "non-active") {
                                $this->articleModel->set('status', $data['status']);
                            } else {
                                $response = [
                                    'status' => 406,
                                    'error' => true,
                                    'message' => "Status can only be 'active' or 'non-active'",
                                ];
                                return $this->respond($response);
                            }
                        }
                        if ($this->articleModel->update($id, $data) && $this->articleModel->update($id, $data_in)) {
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

                $data = $this->articleModel->where('id', $id)->findAll();

                if ($data) {
                    $this->articleModel->delete($id);
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
