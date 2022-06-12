<?php

namespace App\Controllers;

//memanggil model kategori dan artikel
use App\Models\ArticleModel;
use App\Models\CategoryModel;

//memanggil resource controller agar routing dapat berjalan
use CodeIgniter\RESTful\ResourceController;

use Exception;

//memanggil fungsi jwt untuk penggunaan token
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class Category extends ResourceController
{

    // function ini bekerja sebagai construct, untuk memanggil model kategori dan artikel, 
    // agar pada fucntion berikutnya dapat langsung digunakan.
    function __construct()
    {
        $this->categoryModel = new CategoryModel();
        $this->articleModel = new ArticleModel();
    }

    //function dibawah adalah fungsi yang akan melakukan pengecekan terhadap auth-token yang digunakan oleh client
    private function auth_token($auth_token_header)
    {
        if ($auth_token_header) {
            $key = getenv('JWT_SECRET');
            $auth_token_value = $auth_token_header->getValue();
            return JWT::decode($auth_token_value, new Key($key, 'HS256'));
        } else return null;
    }

    // GET -> /category
    //pada function ini, akan ditampilkan semua daftar kategori yang ada pada database
    public function index()
    {
        try {
            // data diurutkan berdasarkan nama secara ascending.
            $data = $this->categoryModel->orderBy('name', 'ASC')->findAll();
            //jika terdapat kategori yang harus ditampilkan
            if (count($data) > 0) {
                $response = [
                    'status' => 200,
                    'error' => false,
                    // maka akan tampil pesan seperti berikut
                    'message' => 'Retrieve list succeed',
                    'data' => $data
                ];
            //jika ternyata tidak ada list yang dapat ditampilkan
            } else {
                $response = [
                    'status' => 404,
                    'error' => false,
                    // maka akan dimunculkan pesan seperti berikut.
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
        //setiap pesan response yang akan diberikan, direturn kembali ke function melalui kode diatas.
        return $this->respond($response, $response['status']);
    }

    // // GET -> /category/$id
    // //function ini berfungsi untuk menampilkan kategori berdasarkan id.
    // public function show($id = null)
    // {
    //     try {
    //         //pertama-tama dilakukan pencari data kategori berdasarkan ID yang dimasukkan oleh client.
    //         $data = $this->categoryModel->where('id', $id)->findAll();
    //         if ($data) {
    //             $response = [
    //                 'status' => 200,
    //                 'error' => false,
    //                 'message' => "Category based on ID: '{$id}' is exist",
    //                 'is_exist' => true,
    //             ];
    //             //jika ID kategori terdapat pada database, akan ditampilkan pesan seperti diatas.
    //         } else {
    //             $response = [
    //                 'status' => 404,
    //                 'error' => false,
    //                 'message' => "Category based on ID: '{$id}' is not found",
    //                 'is_exist' => false,
    //             ];
    //             //tetapi jika ID kategori yang dimasukkan oleh client tidak terdapat pada database, akan ditampilkan pesan seperti diatas.
    //         }
    //     } catch (Exception $ex) {
    //         $response = [
    //             'status' => 500,
    //             'error' => true,
    //             'message' => 'Internal server error, please try again later',
    //         ];
    //     }
    //     return $this->respond($response, $response['status']);
    //     //setiap pesan response yang akan diberikan, direturn kembali ke function melalui kode diatas.
    // }

    // POST -> /category
    //fungsi ini berguna untuk menambah kategori baru kedalam database.
    public function create()
    {
        /*pertama kami ingin memastikan bahwa hanya pengguna biasa tidak dapat menggunakan fitur ini.
         Fitur ini hanya dapat digunakan oleh admin saja.
         Dengan cara mengecek apakah pengguna sudah memasukkan auth-token JWT kedalam request header.
        */
        try {
            $token_decoded = $this->auth_token($this->request->getHeader('auth-token'));
            //jika pengguna belum memasukkan auth-token JWT
            if (!$token_decoded) {
                $response = [
                    'status' => 401,
                    'error' => true,
                    // maka akan muncul pesan seperti berikut diatas.
                    'message' => 'auth-token must be set as header request',
                ];
            } else {
                // kemudian dilakukan pengecekan, apakah pengguna yang mencoba menggunakan function ini adalah admin atau bukan.
                $level = $token_decoded->data->acc_level;
                // Jika bukan admin, maka akan ditampilkan respon seperti berikut.
                if ($level != "admin") {
                    $response = [
                        'status' => 403,
                        'error' => true,
                        'message' => 'Current account does not have permission to create category',
                    ];
                    
                // Jika sudah terverifikasi bahwa yang menggunakan function ini adalah admin.
                } else {
                    // Maka dilakukan pengecekan apakan auth-token yang digunakan masih berlaku.
                    if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                        // rules dan message adalah validasi dan pesan validasi terhadap input yang akan diberikan.
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

                        //Jika input yang diberikan tidak sesuai dengan validasi kami, maka akan ditampilkan pesan error.
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

                            //Jika input sudah sesuai dengan validasi yang ada, 
                            // maka data akan dimasukkan kedalam database, 
                            if ($this->categoryModel->insert($data)) {
                                $response = [
                                    'status' => 201,
                                    "error" => false,
                                    // kemudian akan ditampilkan pesan seperti berikut. 
                                    'message' => 'Category has been added successfully',
                                    'id_category' => $this->categoryModel->getInsertID()
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
                        //Jika ternyata auth-token yang digunakan sudah tidak berlaku lagi 
                        // maka akan ditampilkan pesan seperti berikut.
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

        //setiap pesan response yang akan diberikan, direturn kembali ke function melalui kode diatas.
        return $this->respond($response, $response['status']);
    }

    // PUT -> /category/$id
    //function ini berguna untuk mengubah kategori yang ada berdasarkan ID.
    public function update($id = null)
    {
        
        // Sama seperti pada function create, function update juga hanya dapat digunakan oleh admin.
        try {
            // Sehingga pertama-tama, perlu dipasikan dahulu apakah pengguna sudah memasukkan auth-token mereka.
            $token_decoded = $this->auth_token($this->request->getHeader('auth-token'));
            //jika pengguna belum memasukkan auth-token 
            if (!$token_decoded) {
                $response = [
                    'status' => 401,
                    'error' => true,
                    // maka akan muncul pesan seperti berikut.
                    'message' => 'auth-token must be set as header request',
                ];
            } else {
                // Kemudian dilakukan pengecekan kembali, apakah pengguna yang sedang menjalankan function ini adalah
                // pengguna biasa atau admin. 
                $level = $token_decoded->data->acc_level;
                // Jika ternyata bukan admin 
                if ($level != "admin") {
                    $response = [
                        'status' => 403,
                        'error' => true,
                        // maka akan ditampilkan pesan diatas.
                        'message' => 'Current account does not have permission to edit category',
                    ];
                    
                    
                } else {
                    //kemudian auth-token diperiksa untuk memastikan auth-token masih berlaku
                    if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                        $input = $this->request->getRawInput(); //get all data from input
                        $dataExist = $this->categoryModel->where('id', $id)->findAll();
                        //Jika ID yang dimasukkan tidak terdapat pada database 
                        if (!$dataExist) {
                            $response = [
                                'status' => 404,
                                'error' => false,
                                // maka akan dimunculkan pesan seperti berikut.
                                'message' => "Category based on ID: '{$id}' is not found"
                            ];
                        } else {
                            //rules dan message adalah validasi dan pesan validasi terhadap input yang diberikan.
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


                            //Jika ternyata input yang diberikan tidak sesuai dengan validasi yang ada 
                            if (!$this->validate($rules, $messages)) {
                                $response = [
                                    'status' => 400,
                                    'error' => true,
                                    // maka akan ditampilkan pesan error bedasarkan validasi yang tidak sesuai.
                                    'messages' => $this->validator->getErrors(),
                                ];
                            } else {
                                $data = [
                                    "name" => $input['name'],
                                ];
                                
                                // Jika data sudah sesuai dengan validasi yang ada 
                                // maka selanjutnya data yang lama akan diubah dengan data yang baru berdasarkan ID pada database.
                                if ($this->categoryModel->update($id, $data)) {
                                    $response = [
                                        'status'  => 200,
                                        'error' => false,
                                        // Kemudian akan muncul pesan seperti berikut.
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
                        //Jika auth-token yang diberikan ternyata sudah tidak berlaku 
                        $response = [
                            'status' => 401,
                            'error' => true,
                            // maka akan ditampilkan pesan diatas.
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

        //setiap pesan response yang akan diberikan, direturn kembali ke function melalui kode diatas.
        return $this->respond($response, $response['status']);
    }

    // DELETE -> /category/$id
    //function ini berguna untuk menghapus kategori berdasarkan ID yang diberikan.
    public function delete($id = null)
    {
        /*
        Penghapusan kategori juga hanya dapat dilakukan oleh admin.
        Maka dari itu pertama-tama dilakukan pengecekan apakah pengguna sudah memasukkan auth-token mereka.
        */
        try {
            $token_decoded = $this->auth_token($this->request->getHeader('auth-token'));
            //jika auth-token belum dimasukkan 
            if (!$token_decoded) {
                $response = [
                    'status' => 401,
                    'error' => true,
                    // maka akan ditampilkan pesan seperti berikut.
                    'message' => 'auth-token must be set as header request',
                ];
            } else {
                $level = $token_decoded->data->acc_level;
                //Jika ternyata pengguna yang menggunakan function ini bukanlah admin
                if ($level != "admin") {
                    $response = [
                        'status' => 403,
                        'error' => true,
                        // maka akan ditampilkan pesan seperti berikut.
                        'message' => 'Current account does not have permission to delete category',
                    ];
                } else {
                    //Kemudian auth-token yang diberikan dilakukan pengecekan apakah masih berlaku atau tidak.
                    if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                        //Pertama-tama dilakukan pengecekan, apakah pada database terdapat kategori berdasarkan ID yang dimasukkan.
                        $dataExist = $this->categoryModel->where('id', $id)->findAll();
                        if ($dataExist) {
                            //Kemudian, jika kategori yang akan dihapus ternyata masih digunakan pada suatu artikel
                            $relatedArticle = $this->articleModel->where('id_category', $id)->findAll();
                            if ($relatedArticle) {
                                $response = [
                                    'status' => 409,
                                    'error' => true,
                                    // maka akan ditampilkan pesan seperti berikut.
                                    'message' => 'Category ID is still related to article(s)',
                                ];
                            } else {
                                //Jika data kategori sudah sesuai dengan validasi yang ada
                                if ($this->categoryModel->delete($id)) {
                                    $response = [
                                        'status' => 200,
                                        'error' => false,
                                        //  maka akan dihapus dan ditampilkan pesan seperti berikut.
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
                        //Jika ternyata data kategori berdasarkan ID yang dimasukkan tidak terdapat pada database
                        } else {
                            $response = [
                                'status' => 404,
                                'error' => false,
                                // maka akan ditampilkan pesan seperti berikut.
                                'message' => "Category based on ID: '{$id}' is not found"
                            ];
                        }
                    //Jika token yang dimasukkan sudah tidak berlaku lagi
                    } else {
                        $response = [
                            'status' => 401,
                            'error' => true,
                            // maka akan ditampilkan pesan seperti berikut.
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

        //setiap pesan response yang akan diberikan, direturn kembali ke function melalui kode diatas.
        return $this->respond($response, $response['status']);
    }
}
