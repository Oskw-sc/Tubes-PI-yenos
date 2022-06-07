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
        $this->categoryModel = new CategoryModel();
        $this->commentModel = new CommentModel();
        
        $db = \Config\Database::connect();
        $this->articleDetailView = $db->table('article_details');
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
            $this->articleDetailView->select('id_article, title, cover, status, id_category, category, updated_at');
            $keyword = $this->request->getVar('keyword');
            if (isset($keyword)) $this->articleDetailView->like('title', $keyword);
            $categoryID = $this->request->getVar('id_category');
            if (isset($categoryID)) $this->articleDetailView->where('id_category', $categoryID);
            $status = in_array($this->request->getVar('status'), ['active', 'non-active']) ? $this->request->getVar('status') : null;
            if ($status) $this->articleDetailView->where('status', $status);
            $data = $this->articleDetailView->orderBy('id_article', 'DESC')->get()->getResult();

            if (count($data) > 0) {
                $response = [
                    'status' => 200,
                    'error' => false,
                    'message' => 'Retrieve list succeed',
                    'data' => $data
                ];
            } else {
                $response = [
                    'status' => 404,
                    'error' => false,
                    'message' => 'List of article based on query parameter(s) is empty',
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
        try {
            $data = $this->articleDetailView->where('id_article', $id)->get()->getResult();
            
            if (!$data) {
                $response = [
                    'status' => 404,
                    'error' => false,
                    'message' => "Article based on ID: '{$id}' is not found",
                ];
            } else {
                $this->commentModel->select('comments.id as id_comment, comments.content, comments.id_account, accounts.name as commentator');
                $this->commentModel->join('accounts', 'accounts.id = comments.id_account');
                $comments = $this->commentModel->where('id_article', $id)->orderBy('id_comment', 'DESC')->findAll();
                $comment_count = count($comments);
                $data[0]->comments = $comments;
                $data[0]->comment_count = $comment_count;

                $response = [
                    'status' => 200,
                    'error' => false,
                    'message' => "Article based on ID: '{$id}' is found",
                    'data' => $data[0],
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
                if($level != "admin" && $level != "user") {
                    $response = [
                        'status' => 403,
                        'error' => true,
                        'message' => 'Current account does not have permission to create article',
                    ];
                } else {
                    if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                        $rules = [
                            "title" => "required|max_length[300]",
                            "cover_link" => "required|max_length[300]|valid_url",
                            "content" => "required",
                            "id_category" => "required", // Validasi Exist Id category
                        ];

                        $messages = [
                            "title" => [
                                "required" => "Title is required",
                                "max_length" => "Title can be filled by a maximum of 300 characters",
                            ],
                            "cover_link" => [
                                "required" => "Cover link is required",
                                "max_length" => "Cover link can be filled by a maximum of 300 characters",
                                "valid_url" => "Cover link must be filled by valid URL",
                            ],
                            "content" => [
                                "required" => "Content is required"
                            ],
                            "id_category" => [
                                "required" => "ID of category is required"
                            ]
                        ];

                        if (!$this->validate($rules, $messages)) {
                            $response = [
                                'status' => 400,
                                'error' => true,
                                'messages' => $this->validator->getErrors(),
                            ];
                        } else {
                            $id_category = $this->request->getVar("id_category");
                            $is_exist = $this->categoryModel->where('id', $id_category)->findAll();
                            if (!$is_exist) {
                                $response = [
                                    'status' => 404,
                                    'error' => false,
                                    'message' => "ID of category: '{$id_category}' does not exist",
                                ];
                            } else {
                                $data = [
                                    "id_account" => $token_decoded->data->acc_id,
                                    "id_category" => $this->request->getVar("id_category"),
                                    "title" => $this->request->getVar("title"),
                                    "cover" => $this->request->getVar("cover_link"),
                                    "description" => $this->request->getVar("content")
                                ];

                                if ($this->articleModel->insert($data)) {
                                    $response = [
                                        'status' => 201,
                                        'error' => false,
                                        'messages' => 'Article has been created successfully',
                                        'id_article' => $this->articleModel->getInsertID()
                                    ];
                                } else {
                                    $response = [
                                        'status' => 500,
                                        'error' => true,
                                        'messages' => 'Internal server error, please try again later',
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
                    $data = $this->articleModel->where('id', $id)->first();
                    if ($data) {
                        $level = $token_decoded->data->acc_level;
                        if ($level != "admin" && $data['id_account'] != $token_decoded->data->acc_id) {
                            $response = [
                                'status' => 403,
                                'error' => true,
                                'message' => 'Current account does not have permission to delete this article',
                            ];
                        } else {
                            if ($this->articleModel->delete($id)) {
                                $response = [
                                    'status' => 200,
                                    'error' => false,
                                    'message' => "Article and its comment(s) based on ID: '$id' has been deleted",
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
                            'message' => "Article based on ID: '{$id}' is not found"
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
