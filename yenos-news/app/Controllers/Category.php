<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;
use \Firebase\JWT\JWT;

use \Firebase\JWT\Key;

class Category extends ResourceController
{
    public function create()
    {
        $key = getenv('JWT_SECRET');
        $authHeader = $this->request->getHeader("Authorization");
        if(!$authHeader) return $this->failUnauthorized('auth-token must be passed as header request');
        $token = $authHeader->getValue();

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $level = $decoded->data->acc_level;
            if($level == "user") return $this->failForbidden('You dont have a permission to add category');

            if ($decoded && ($decoded->exp - time() > 0)) {

                $rules = [
                    "name" => "required|max_length[255]",
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
                } else {
                    $categoryModel = new CategoryModel();

                    $data = [
                        "name" => $this->request->getVar("name"),
                    ];

                    if ($categoryModel->insert($data)) {
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
}
