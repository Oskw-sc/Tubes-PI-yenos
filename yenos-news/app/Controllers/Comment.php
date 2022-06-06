<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\UserModel;
use App\Models\CommentModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;
use \Firebase\JWT\JWT;

use \Firebase\JWT\Key;

class Comment extends ResourceController
{

    function __construct()
    {
        $this->model = new CommentModel();
        $this->ArticleModel = new ArticleModel();
        $this->UserModel = new UserModel();
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
            $data_array = $this->model->where('id', $id)->first();
            $id_article = $data_array['id_article'];

            $is_exist = $this->ArticleModel->where('id', $id_article)->first();

            $data_detail = [
                "id" => $data_array['id'],
                "id_account" => $data_array['id_account'],
                "id_article" => $id_article,
                "article_title" => $is_exist['title'],
                "comment" => $data_array['content'],
            ];
            return $this->respond($data_detail, 200);
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
                    "content" => "required|max_length[300]",
                    "id_article" => "required", // Validasi Exist Id article
                ];

                $messages = [
                    "content" => [
                        "required" => "content is required"
                    ],
                    "id_article" => [
                        "required" => "Id article is required"
                    ]
                ];

                if (!$this->validate($rules, $messages)) {
                    $response = [
                        'status' => 500,
                        'message' => $this->validator->getErrors(),
                    ];
                } else {
                    $id_article = $this->request->getVar("id_article");
                    $is_exist = $this->ArticleModel->where('id', $id_article)->findAll();

                    if (!$is_exist) {
                        return $this->failNotFound("article not found by id : $id_article");;
                    } else {
                        $data = [
                            "id_account" => $decoded->data->acc_id,
                            "id_article" => $this->request->getVar("id_article"),
                            "content" => $this->request->getVar("content"),
                        ];

                        if ($this->model->insert($data)) {
                            $id_article = $this->request->getVar("id_article");

                            $is_exist = $this->ArticleModel->where('id', $id_article)->first();
                            $title = $is_exist['title'];

                            $response = [
                                'code' => 201,
                                'messages' => "comment created on article : '$title'",
                            ];

                            return $this->respond($response);
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

                $id_account = $decoded->data->acc_id;
                $data_account = $this->UserModel->where('id', $id_account)->first(); //ambil user

                $data = $this->model->where('id', $id)->findAll();

                if ($data) {

                    $data_array = $this->model->where('id', $id)->first(); //arraykan data komentar
                    $id_account_comment = $data_array['id_account']; // ambil id user dari komentar
                    $user_lever = $data_account['level'];

                    // var_dump($id_account_comment);
                    if ($user_lever == "admin") {
                        $this->model->delete($id);
                        $response = [
                            'status' => 200,
                            'error' => null,
                            'messages' => [
                                'success' => "Successfully delete comment  by id : $id",
                            ]
                        ];

                        return $this->respondDeleted($response);
                    } elseif ($user_lever == 'user' && $id_account == $id_account_comment) {
                        $this->model->delete($id);
                        $response = [
                            'status' => 200,
                            'error' => null,
                            'messages' => [
                                'success' => "Successfully delete comment  by id : $id",
                            ]
                        ];
                        return $this->respondDeleted($response);
                    } else {
                        return $this->failForbidden("You are not an admin or the owner of this comment");
                    }
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
