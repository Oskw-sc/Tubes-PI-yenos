<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;
use \Firebase\JWT\JWT;

use \Firebase\JWT\Key;

class Article extends ResourceController
{
    public function create_article()
    {
        $iat = time(); // current timestamp value

        $rules = [
            "title" => "required",
            "cover" => "required",
            "description" => "required",
            "id_account" => "required",
            "id_category" => "required",
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
            "id_account" => [
                "required" => "Id Account is required"
            ],
            "id_category" => [
                "required" => "Id Category is required"
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
            $articleModel = new ArticleModel();

            $data = [
                "id_account" => $this->request->getVar("id_account"),
                "id_category" => $this->request->getVar("id_category"),
                "title" => $this->request->getVar("title"),
                "cover" => $this->request->getVar("cover"),
                "description" => $this->request->getVar("description"),
                "status" => "non-active"
            ];

            if ($articleModel->insert($data)) {
                $response = [
                    'status' => 201,
                    "error" => false,
                    'messages' => 'Article has been added successfully',
                    'data' => []
                ];
            } else {
                $response = [
                    'status' => 500,
                    "error" => true,
                    'messages' => 'Failed to create article',
                    'data' => []
                ];
            }
        }

        return $this->respondCreated($response);
    }
}
