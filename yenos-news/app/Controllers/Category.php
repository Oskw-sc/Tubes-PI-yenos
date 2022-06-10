<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\CategoryModel;
//memanggil model kategori dan artikel

use CodeIgniter\RESTful\ResourceController;
//memanggil resource controller agar routing dapat berjalan

use Exception;

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
//memanggil fungsi jwt untuk penggunaan token

class Category extends ResourceController
{

    function __construct()
    {
        $this->categoryModel = new CategoryModel();
        $this->articleModel = new ArticleModel();
    }
    // function diatas bekerja sebagai construct, untuk memanggil model kategori dan artikel, agar pada fucntion berikutnya dapat langsung digunakan.

    //function dibawah adalah fungsi yang akan melakukan pengecekan terhadap auth-token yang digunakan oleh client
    private function auth_token($auth_token_header)
    {
        if ($auth_token_header) {
            $key = getenv('JWT_SECRET');
            $auth_token_value = $auth_token_header->getValue();
            return JWT::decode($auth_token_value, new Key($key, 'HS256'));
        } else return null;
    }

    //pada function ini, akan ditampilkan semua daftar kategori yang ada pada database, dan diurutkan berdasarkan nama secara ascending.
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
                //jika ternyata tidak ada list yang dapat ditampilkan, akan dimunculkan pesan seperti diatas.
            }
        } catch (Exception $ex) {
            $response = [
                'status' => 500,
                'error' => true,
                'message' => 'Internal server error, please try again later',
            ];
        }
        return $this->respond($response, $response['status']);
        //setiap pesan response yang akan diberikan, direturn kembali ke function melalui kode diatas.
    }

    //function ini berfungsi untuk menampilkan kategori berdasarkan id.
    public function show($id = null)
    {
        try {
            //pertama-tama dilakukan pencari data kategori berdasarkan ID yang dimasukkan oleh client.
            $data = $this->categoryModel->where('id', $id)->findAll();
            if ($data) {
                $response = [
                    'status' => 200,
                    'error' => false,
                    'message' => "Category based on ID: '{$id}' is exist",
                    'is_exist' => true,
                ];
                //jika ID kategori terdapat pada database, akan ditampilkan pesan seperti diatas.
            } else {
                $response = [
                    'status' => 404,
                    'error' => false,
                    'message' => "Category based on ID: '{$id}' is not found",
                    'is_exist' => false,
                ];
                //tetapi jika ID kategori yang dimasukkan oleh client tidak terdapat pada database, akan ditampilkan pesan seperti diatas.
            }
        } catch (Exception $ex) {
            $response = [
                'status' => 500,
                'error' => true,
                'message' => 'Internal server error, please try again later',
            ];
        }
        return $this->respond($response, $response['status']);
        //setiap pesan response yang akan diberikan, direturn kembali ke function melalui kode diatas.
    }

    //fungsi ini berguna untuk menambah kategori baru kedalam database.
    public function create()
    {
        /*pertama kami ingin memastikan bahwa hanya pengguna biasa tidak dapat menggunakan fitur ini.
         Fitur ini hanya dapat digunakan oleh admin saja.
         Dengan cara mengecek apakah pengguna sudah memasukkan auth-token JWT kedalam request header.
        */
        try {
            $token_decoded = $this->auth_token($this->request->getHeader('auth-token'));
            if (!$token_decoded) {
                $response = [
                    'status' => 401,
                    'error' => true,
                    'message' => 'auth-token must be set as header request',
                ];
                //jika pengguna belum memasukkan auth-token JWT, maka akan muncul pesan seperti diatas.
            } else {
                $level = $token_decoded->data->acc_level;
                if ($level != "admin") {
                    $response = [
                        'status' => 403,
                        'error' => true,
                        'message' => 'Current account does not have permission to create category',
                    ];
                    /* kemudian dilakukan pengecekan, apakah pengguna yang mencoba menggunakan function ini adalah admin atau bukan.
                    Jika bukan admin, maka akan ditampilkan pesan diatas.
                    */
                } else {
                    /*
                    Jika sudah terverifikasi bahwa yang menggunakan function ini adalah admin.
                    Maka dilakukan pengecekan apakan auth-token yang digunakan masih berlaku.
                    */
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
                        // rules dan message adalah validasi dan pesan validasi terhadap input yang akan diberikan.

                        if (!$this->validate($rules, $messages)) {
                            $response = [
                                'status' => 400,
                                'error' => true,
                                'messages' => $this->validator->getErrors(),
                            ];
                            //Jika input yang diberikan tidak sesuai dengan validasi kami, maka akan ditampilkan pesan error.
                        } else {
                            $data = [
                                "name" => $this->request->getVar("name"),
                            ];

                            if ($this->categoryModel->insert($data)) {
                                $response = [
                                    'status' => 201,
                                    "error" => false,
                                    'message' => 'Category has been added successfully',
                                    'id_category' => $this->categoryModel->getInsertID()
                                ];
                                //Jika input sudah sesuai dengan validasi yang ada, maka data akan dimasukkan kedalam database, kemudian akan ditampilkan pesan seperti diatas. 
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
                        //Jika ternyata auth-token yang digunakan sudah tidak berlaku lagi, maka akan ditampilkan pesan seperti diatas.
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
        //setiap pesan response yang akan diberikan, direturn kembali ke function melalui kode diatas.
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

                                if ($this->categoryModel->update($id, $data)) {
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
                            $relatedArticle = $this->articleModel->where('id_category', $id)->findAll();
                            if ($relatedArticle) {
                                $response = [
                                    'status' => 409,
                                    'error' => true,
                                    'message' => 'Category ID is still related to article(s)',
                                ];
                            } else {
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
