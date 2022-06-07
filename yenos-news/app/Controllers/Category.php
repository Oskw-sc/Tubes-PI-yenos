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
        $this->categoryModel = new CategoryModel();
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
            $data = $this->categoryModel->orderBy('name', 'ASC')->findAll();
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
        try {
            $data = $this->categoryModel->where('id', $id)->findAll();
            if ($data) {
                $response = [
                    'status' => 200,
                    'error' => false,
                    'message' => "Category based on ID: '{$id}' is exist",
                    'is_exist' => true,
                ];
            } else {
                $response = [
                    'status' => 404,
                    'error' => false,
                    'message' => "Category based on ID: '{$id}' is not found",
                    'is_exist' => false,
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
                if($level != "admin") {
                    $response = [
                        'status' => 403,
                        'error' => true,
                        'message' => 'Current account does not have permission to create category',
                    ];
                } else {
                    if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                        $rules = [
                            "name" => "required|max_length[255]|is_unique[categories.name]",
                        ];
                        $messages = [
                            "name" => [
                                "required" => "category'name is required",
                                "max_length" => "category's name can be filled by a maximum of 255 characters",
                                "is_unique" => "category's name existed, please fill by another one",
                            ],
                        ];

                        if (!$this->validate($rules, $messages)) {
                            $response = [
                                'status' => 400,
                                'error' => true,
                                'messages' => $this->validator->getErrors(),
                            ];
                        } else {
                            $data = [
                                "name" => $this->request->getVar("name"),
                            ];
                
                            if ($this->categoryModel->insert($data)) {
                                $response = [
                                    'status' => 201,
                                    "error" => false,
                                    'message' => 'Category has been added successfully',
                                ];
                            } else {
                                $response = [
                                    'status' => 500,
                                    "error" => true,
                                    'message' => 'Internal server error, please try again later',
                                ];
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
                if ($level != "admin") {
                    $response = [
                        'status' => 403,
                        'error' => true,
                        'message' => 'Current account does not have permission to edit category',
                    ];
                } else {
                    if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                        $input = $this->request->getRawInput(); //get all data from input
                        $dataExist = $this->categoryModel->where('id', $id)->findAll();
                        if (!$dataExist) {
                            $response = [
                                'status' => 404,
                                'error' => false,
                                'message' => "Category based on ID: '{$id}' is not found"
                            ];
                        } else {
                            $rules = [
                                "name" => "required|max_length[255]|is_unique[categories.name]",
                            ];
                            $messages = [
                                "name" => [
                                    "required" => "edited category'name is required",
                                    "max_length" => "edited category's name can be filled by a maximum of 255 characters",
                                    "is_unique" => "edited category's name existed, please fill by another one",
                                ],
                            ];

                            if (!$this->validate($rules, $messages)) {
                                $response = [
                                    'status' => 400,
                                    'error' => true,
                                    'messages' => $this->validator->getErrors(),
                                ];
                            } else {
                                $data = [
                                    "name" => $input['name'],
                                ];

                                if($this->categoryModel->update($id, $data)) {
                                    $response = [
                                        'status'  => 200,
                                        'error' => false, 
                                        'message' => 'Category has been edited successfully',
                                    ];
                                } else {
                                    $response = [
                                        'status' => 500,
                                        'error' => true,
                                        'message' => "Internal server error, please try again later",
                                    ];
                                };
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
                $level = $token_decoded->data->acc_level;
                if ($level != "admin") {
                    $response = [
                        'status' => 403,
                        'error' => true,
                        'message' => 'Current account does not have permission to delete category',
                    ];
                } else {
                    if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                        $dataExist = $this->categoryModel->where('id', $id)->findAll();
                        if ($dataExist) {
                            if ($this->categoryModel->delete($id)) {
                                $response = [
                                    'status' => 200,
                                    'error' => false,
                                    'message' => 'Category has been deleted successfully',
                                ];
                            } else {
                                $response = [
                                    'status' => 500,
                                    'error' => true,
                                    'message' => "Internal server error, please try again later",
                                ];
                            }
                        } else {
                            $response = [
                                'status' => 404,
                                'error' => false,
                                'message' => "Category based on ID: '{$id}' is not found"
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
