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
}
