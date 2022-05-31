<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;
use \Firebase\JWT\JWT;

use \Firebase\JWT\Key;

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

                    $key = getenv('JWT_SECRET');

                    $iat = time(); // current timestamp value
                    $nbf = $iat + 10;
                    $exp = $iat + 3600;

                    $payload = array(
                        "iss" => "The_claim",
                        "aud" => "The_Aud",
                        "iat" => $iat, // issued at
                        "nbf" => $nbf, //not before in seconds
                        "exp" => $exp, // expire time in seconds
                        "data" => [
                            'acc_id' => $userdata['id'],
                            'acc_name' => $userdata['name'],
                            'acc_username' => $userdata['username'],
                            'acc_level' => $userdata['level'],
                        ],
                    );

                    $token = JWT::encode($payload, $key, 'HS256');

                    $response = [
                        'status' => 200,
                        'error' => false,
                        'messages' => 'User logged in successfully',
                        'data' => [
                            'token' => $token
                        ]
                    ];
                    return $this->respondCreated($response);
                } else {
                    $response = [
                        'status' => 500,
                        'error' => true,
                        'messages' => 'Incorrect log in credentials',
                        'data' => []
                    ];
                    return $this->respondCreated($response);
                }
            } else {
                $response = [
                    'status' => 500,
                    'error' => true,
                    'messages' => 'Username not found',
                    'data' => []
                ];
                return $this->respondCreated($response);
            }
        }
    }

    public function details()
    {
        $key = getenv('JWT_SECRET');
        $authHeader = $this->request->getHeader("Authorization");
        $token = $authHeader->getValue();

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            if ($decoded && ($decoded->exp - time() > 0)) {
                $response = [
                    'status' => 200,
                    'error' => false,
                    'messages' => 'User profile',
                    'data' => [
                        'profile' => $decoded,
                        'remain' => $decoded->exp - time()
                    ]
                ];
                return $this->respondCreated($response);
            }
        } catch (Exception $ex) {
            $response = [
                'status' => 401,
                'error' => true,
                'messages' => 'Access denied',
                'data' => []
            ];
            return $this->respondCreated($response);
        }
    }
}