<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;
use \Firebase\JWT\JWT;

class User extends ResourceController
{
    public function register()
    {
        $rules = [
            "name" => "required",
            "username" => "required|is_unique[accounts.username]",
            "password" => "required",
        ];

        $messages = [
            "name" => [
                "required" => "Name is required"
            ],
            "username" => [
                "required" => "username is required",
                "is_unique" => "username has been used"
            ],
            "password" => [
                "required" => "password is required"
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
            $userModel = new UserModel();

            $data = [
                "name" => $this->request->getVar("name"),
                "username" => $this->request->getVar("username"),
                "password" => password_hash($this->request->getVar("password"), PASSWORD_BCRYPT),
                "level" => "user"
            ];

            if ($userModel->insert($data)) {
                $response = [
                    'status' => 201,
                    "error" => false,
                    'messages' => 'Successfully, user has been registered',
                    'data' => []
                ];
            } else {
                $response = [
                    'status' => 500,
                    "error" => true,
                    'messages' => 'Failed to create user',
                    'data' => []
                ];
            }
        }

        return $this->respondCreated($response);
    }

    private function getKey()
    {
        return "my_application_secret";
    }

    public function login()
    {
        $rules = [
            "username" => "required",
            "password" => "required",
        ];

        $messages = [
            "username" => [
                "required" => "username is required"
            ],
            "password" => [
                "required" => "password is required"
            ],
        ];

        if (!$this->validate($rules, $messages)) {

            $response = [
                'status' => 500,
                'error' => true,
                'message' => $this->validator->getErrors(),
                'data' => []
            ];

            return $this->respondCreated($response);
            
        } else {
            $userModel = new UserModel();

            $userdata = $userModel->where("username", $this->request->getVar("username"))->first();

            if (!empty($userdata)) {

                if (password_verify($this->request->getVar("password"), $userdata['password'])) {

                    $key = $this->getKey();

                    $iat = time(); // current timestamp value
                    $nbf = $iat + 10;
                    $exp = $iat + 3600;

                    $payload = array(
                        "iss" => "The_claim",
                        "aud" => "The_Aud",
                        "iat" => $iat, // issued at
                        "nbf" => $nbf, //not before in seconds
                        "exp" => $exp, // expire time in seconds
                        "data" => $userdata,
                    );

                    $token = JWT::encode($payload, $key, 'HS256');

                    $response = [
                        'status' => 200,
                        'error' => false,
                        'messages' => 'User logged In successfully',
                        'data' => [
                            'token' => $token
                        ]
                    ];
                    return $this->respondCreated($response);
                } else {

                    $response = [
                        'status' => 500,
                        'error' => true,
                        'messages' => 'Incorrect details',
                        'data' => []
                    ];
                    return $this->respondCreated($response);
                }
            } else {
                $response = [
                    'status' => 500,
                    'error' => true,
                    'messages' => 'User not found',
                    'data' => []
                ];
                return $this->respondCreated($response);
            }
        }
    }

    public function details()
    {
        $key = $this->getKey();
        $authHeader = $this->request->getHeader("Authorization");
        $authHeader = $authHeader->getValue();
        $token = $authHeader;

        try {
            $decoded = JWT::decode($token, $key, array("HS256"));

            if ($decoded) {

                $response = [
                    'status' => 200,
                    'error' => false,
                    'messages' => 'User details',
                    'data' => [
                        'profile' => $decoded
                    ]
                ];
                return $this->respondCreated($response);
            }
        } catch (Exception $ex) {
          
            $response = [
                'status' => 401,
                'error' => true,
                'messages' => 'Access denied',
                'data' => [
                    'token' => $token
                ]
            ];
            return $this->respondCreated($response);
        }
    }
}