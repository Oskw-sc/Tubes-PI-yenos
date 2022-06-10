<?php

namespace App\Controllers;

use App\Models\UserModel;
// Memanggil model kategori, artikel, dan komentar

use CodeIgniter\RESTful\ResourceController;
// Memanggil resource controller agar routing dapat berjalan

use Exception;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
// Memanggil fungsi jwt untuk penggunaan token

class User extends ResourceController
{
    // Membuat fungsi untuk memanggil model user agar pada function berikutnya dapat langsung digunakan.
    function __construct()
    {
        $this->userModel = new UserModel();
    }

    // POST -> /account/register
    // Kode ini bertujuan untuk membuat akun user yang baru.
    public function register()
    {
        $rules = [
            "name" => "required|max_length[255]",
            "username" => "required|is_unique[accounts.username]|max_length[50]",
            "password" => "required|max_length[60]",
        ];
        $messages = [
            "name" => [
                "required" => "name is required",
                "max_length" => "name can be filled by a maximum of 255 characters",
            ],
            "username" => [
                "required" => "username is required",
                "is_unique" => "username has been registered, please fill by another one",
                "max_length" => "username can be filled by a maximum of 50 characters",
            ],
            "password" => [
                "required" => "password is required",
                "max_length" => "password can be filled by a maximum of 60 characters",
            ],
        ];

        // Rules dan message adalah validasi dan pesan validasi terhadap input yang akan diberikan.
        if (!$this->validate($rules, $messages)) {
            $response = [
                'status' => 400,
                'error' => true,
                'messages' => $this->validator->getErrors(),
            ];
            // Jika input yang diberikan tidak sesuai dengan validasi kami, maka akan ditampilkan pesan error.
        } else {
            $data = [
                "name" => $this->request->getVar("name"),
                "username" => $this->request->getVar("username"),
                "password" => password_hash($this->request->getVar("password"), PASSWORD_BCRYPT),
                "level" => "user",
            ];

            if ($this->userModel->insert($data)) {
                $response = [
                    'status' => 201,
                    "error" => false,
                    'message' => 'New user account has been successfully registered',
                    'id_created' => $this->userModel->getInsertID(),
                ];
                // Jika input sudah sesuai dengan validasi yang ada, maka data akan dimasukkan kedalam database, kemudian akan ditampilkan pesan seperti diatas.
            } else {
                $response = [
                    'status' => 500,
                    "error" => true,
                    'message' => 'Internal server error, please try again later',
                ];
            }
        }

        return $this->respond($response, $response['status']);
        // setiap pesan response yang akan diberikan, direturn kembali ke function melalui kode diatas.
    }

    // POST -> /account/login
    // Kode ini bertujuan untuk mendapatkan auth-token dari sebuah akun sebagai bentuk proses log in.
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

        // Rules dan message adalah validasi dan pesan validasi terhadap input yang akan diberikan.
        if (!$this->validate($rules, $messages)) {
            $response = [
                'status' => 400,
                'error' => true,
                'messages' => $this->validator->getErrors(),
            ];
            // Jika input yang diberikan tidak sesuai dengan validasi kami, maka akan ditampilkan pesan error.
        } else {
            $userdata = $this->userModel->where("username", $this->request->getVar("username"))->first();
            // Membuat token 
            if (!empty($userdata)) {
                if (password_verify($this->request->getVar("password"), $userdata['password'])) {
                    $key = getenv('JWT_SECRET');

                    $iat = time(); // current timestamp value
                    $nbf = $iat + 10;
                    $exp = $iat + 3600;
                    $payload = [
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
                    ];

                    $token = JWT::encode($payload, $key, 'HS256');

                    $response = [
                        'status' => 200,
                        'error' => false,
                        'message' => 'Credentials are correct, here are your account temporary auth-token and account level',
                        'data' => [
                            'auth-token' => $token,
                            'level' => $userdata['level']
                        ]
                    ];
                    // Jika pembuatan token berhasil, maka akan ditampilkan pesan seperti berikut.
                } else {
                    $response = [
                        'status' => 401,
                        'error' => true,
                        'message' => 'Incorrect log in credentials',
                    ];
                    // Jika username atau password salah, maka akan ditampilkan pesan seperti berikut.
                }
            } else {
                $response = [
                    'status' => 404,
                    'error' => false,
                    'message' => 'Account with this username has not been registered',
                ];
                // Jika username atau password tidak terdaftar pada database, maka akan ditampilkan pesan seperti berikut.
            }
        }

        return $this->respond($response, $response['status']);
        // setiap pesan response yang akan diberikan, direturn kembali ke function melalui kode diatas.
    }
}
