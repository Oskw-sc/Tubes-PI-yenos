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
        $this->commentModel = new CommentModel();
        $this->articleModel = new ArticleModel();
        $this->userModel = new UserModel();
    }

    private function auth_token($auth_token_header)
    {
        if ($auth_token_header) {
            $key = getenv('JWT_SECRET');
            $auth_token_value = $auth_token_header->getValue();
            return JWT::decode($auth_token_value, new Key($key, 'HS256'));
        } else return null;
    }

    public function index()
    {

        $data = $this->commentModel->orderBy('id', 'asc')->findAll();
        return $this->respond($data, 200);
    }

    public function show($id = null)
    {

        $data = $this->commentModel->where('id', $id)->findAll();

        if ($data) {
            $data_array = $this->commentModel->where('id', $id)->first();
            $id_article = $data_array['id_article'];

            $is_exist = $this->articleModel->where('id', $id_article)->first();

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
        try {
            $token_decoded = $this->auth_token($this->request->getHeader('auth-token'));
            if (!$token_decoded) {
                $response = [
                    'status' => 401,
                    'error' => true,
                    'message' => 'auth-token must be set as header request',
                ];
            } else {
                $level = $token_decoded->data->acc_level;
                if ($level != "admin" && $level != "user") {
                    $response = [
                        'status' => 403,
                        'error' => true,
                        'message' => 'Current account does not have permission to create comment',
                    ];
                } else {
                    if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                        $rules = [
                            "content" => "required",
                            "id_article" => "required", // Validasi Exist Id article
                        ];

                        $messages = [
                            "content" => [
                                "required" => "Content is required"
                            ],
                            "id_article" => [
                                "required" => "ID of article is required"
                            ]
                        ];

                        if (!$this->validate($rules, $messages)) {
                            $response = [
                                'status' => 400,
                                'error' => true,
                                'messages' => $this->validator->getErrors(),
                            ];
                        } else {
                            $id_article = $this->request->getVar("id_article");
                            $is_exist = $this->articleModel->where('id', $id_article)->findAll();

                            if (!$is_exist) {
                                $response = [
                                    'status' => 404,
                                    'error' => false,
                                    'message' => "ID of article: '{$id_article}' does not exist",
                                ];
                            } else {
                                $data = [
                                    "id_account" => $token_decoded->data->acc_id,
                                    "id_article" => $this->request->getVar("id_article"),
                                    "content" => $this->request->getVar("content"),
                                ];

                                if ($this->commentModel->insert($data)) {
                                    $is_exist = $this->articleModel->where('id', $id_article)->first();
                                    $title = $is_exist['title'];

                                    $response = [
                                        'status' => 201,
                                        'error' => false,
                                        'message' => "Comment created on article: '$title'",
                                        'id_comment' => $this->commentModel->getInsertID(),
                                    ];
                                } else {
                                    $response = [
                                        'status' => 500,
                                        'error' => true,
                                        'message' => 'Internal server error, please try again later',
                                    ];
                                }
                            }
                        }
                    } else {
                        $response = [
                            'status' => 401,
                            'error' => true,
                            'message' => 'auth-token is invalid, might be expired',
                        ];
                    }
                }
            }
        } catch (Exception $ex) {
            $response = [
                'status' => 401,
                'error' => true,
                'message' => 'auth-token is invalid, might be expired',
            ];
        }
        return $this->respond($response, $response['status']);
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
                $data_account = $this->userModel->where('id', $id_account)->first(); //ambil user

                $data = $this->commentModel->where('id', $id)->findAll();

                if ($data) {

                    $data_array = $this->commentModel->where('id', $id)->first(); //arraykan data komentar
                    $id_account_comment = $data_array['id_account']; // ambil id user dari komentar
                    $user_lever = $data_account['level'];

                    // var_dump($id_account_comment);
                    if ($user_lever == "admin") {
                        $this->commentModel->delete($id);
                        $response = [
                            'status' => 200,
                            'error' => null,
                            'messages' => [
                                'success' => "Successfully delete comment  by id : $id",
                            ]
                        ];

                        return $this->respondDeleted($response);
                    } elseif ($user_lever == 'user' && $id_account == $id_account_comment) {
                        $this->commentModel->delete($id);
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
