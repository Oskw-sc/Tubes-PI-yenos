<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;
use \Firebase\JWT\JWT;

use \Firebase\JWT\Key;

class Category extends ResourceController
{
    public function create_category()
    {
        $rules = [
            "name" => "required",
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
}
