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
        try {
            $keyword = $this->request->getVar('keyword');
            if (isset($keyword)) $this->commentModel->like('content', $keyword);
            $id_article = $this->request->getVar('id_article');
            if (isset($id_article)) $this->commentModel->where('id_article', $id_article);
            $data = $this->commentModel->orderBy('id', 'DESC')->findAll();

            if ($data) {
                $response = [
                    'status' => 200,
                    'error' => false,
                    'message' => 'Retrieve comment(s) succeed',
                    'data' => $data
                ];
            } else {
                $response = [
                    'status' => 404,
                    'error' => false,
                    'message' => 'Comment(s) based on query parameter(s) is not found',
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
        try {
            $token_decoded = $this->auth_token($this->request->getHeader('auth-token'));
            if (!$token_decoded) {
                $response = [
                    'status' => 401,
                    'error' => true,
                    'message' => 'auth-token must be set as header request',
                ];
            } else {
                if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                    $comment = $this->commentModel->where('id', $id)->first();
                    if ($comment) {
                        $level = $token_decoded->data->acc_level;
                        $article = $this->articleModel->where('id', $comment['id_article'])->first();
                        if ($level != "admin" && $comment['id_account'] != $token_decoded->data->acc_id && $article['id_account'] != $token_decoded->data->acc_id) {
                            $response = [
                                'status' => 403,
                                'error' => true,
                                'message' => 'Current account does not have permission to delete comment',
                            ];
                        } else {
                            if ($this->commentModel->delete($id)) {
                                $response = [
                                    'status' => 200,
                                    'error' => false,
                                    'message' => "Comment based on ID: '$id' has been deleted",
                                ];
                            } else {
                                $response = [
                                    'status' => 500,
                                    'error' => true,
                                    'message' => "Internal server error, please try again later",
                                ];
                            }
                        }
                    } else {
                        $response = [
                            'status' => 404,
                            'error' => false,
                            'message' => "Comment based on ID: '{$id}' is not found"
                        ];
                    }
                } else {
                    $response = [
                        'status' => 401,
                        'error' => true,
                        'message' => 'auth-token is invalid, might be expired',
                    ];
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
}
