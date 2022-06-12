<?php

namespace App\Controllers;

//memanggil model kategori, artikel, dan komentar
use App\Models\ArticleModel;
use App\Models\UserModel;
use App\Models\CommentModel;

//memanggil resource controller agar routing dapat berjalan
use CodeIgniter\RESTful\ResourceController;

use Exception;

//memanggil fungsi jwt untuk penggunaan token
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class Comment extends ResourceController
{

    // function ini bekerja sebagai construct, 
    // untuk memanggil model komentar, kategori, dan artikel 
    // agar pada function berikutnya dapat langsung digunakan.
    function __construct()
    {
        $this->commentModel = new CommentModel();
        $this->articleModel = new ArticleModel();
        $this->userModel = new UserModel();
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

    // GET -> /comment
    // GET -> /comment?keyword=&id_article=
    //function ini berguna untuk menampilkan komentar
    public function index()
    {
        //Ada beberapa cara dalam mencari komentar pada API kami.
        try {
            //cara pertama adalah menggunakan keyword. 
            // Pengguna hanya perlu menuliskan keyword yang ingin dicari, 
            // maka akan ditampilkan komentar yang berisikan kata-kata dari keyword yang di inputkan.
            $keyword = $this->request->getVar('keyword');
            if (isset($keyword)) $this->commentModel->like('content', $keyword);

            //cara kedua adalah dengan mencari berdasarkan ID dari artikel. 
            // Sehingga jika pengguna ingin mengetahui komentar apa saja yang ada pada artikel tertentu, maka cara ini dapat digunakan.
            $id_article = $this->request->getVar('id_article');
            if (isset($id_article)) $this->commentModel->where('id_article', $id_article);

            //tetapi jika pengguna tidak memasukkan input keyword ataupun id_article, 
            // maka secara default akan ditampilkan seluruh komentar yang ada pada database.
            $data = $this->commentModel->orderBy('id', 'DESC')->findAll();

            //Jika data komentar yang dicari ternyata ada pada database, maka akan ditampilkan pesan seperti diatas.
            if ($data) {
                $response = [
                    'status' => 200,
                    'error' => false,
                    'message' => 'Retrieve comment(s) succeed',
                    'data' => $data
                ];
            //Tetapi jika data dari komentar yang dicari tidak ada pada database, maka akan ditampilkan seperti diatas.
            } else {
                $response = [
                    'status' => 404,
                    'error' => false,
                    'message' => 'Comment(s) based on query parameter(s) is not found',
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

    // // GET -> /comment/$id
    // //function ini berguna untuk menampilkan komentar berdasarkan ID
    // public function show($id = null)
    // {
    //     //pertama-tama akan dicari data ID komentar didalam database.
    //     $data = $this->commentModel->where('id', $id)->findAll();

    //     if ($data) {
    //         $data_array = $this->commentModel->where('id', $id)->first();
    //         $id_article = $data_array['id_article'];

    //         $is_exist = $this->articleModel->where('id', $id_article)->first();

    //         $data_detail = [
    //             "id" => $data_array['id'],
    //             "id_account" => $data_array['id_account'],
    //             "id_article" => $id_article,
    //             "article_title" => $is_exist['title'],
    //             "comment" => $data_array['content'],
    //         ];
    //         return $this->respond($data_detail, 200);
    //         //jika ternyata data tersebut ada pada database, akan ditampilkan pesan seperti diatas.
    //     } else {
    //         return $this->failNotFound("Cannot found article by id : $id");
    //         //jika ID komentar tidak terdapat pada database, akan ditampilkan pesan seperti diatas.
    //     }
    // }

    // POST -> /comment
    //function ini berguna untuk menambah komentar
    public function create()
    {
        /*
        Untuk dapat menambah komentar, kami hanya memperbolehkan pengguna (level user) dan admin.
        Maka dari itu akan dilakukan terlebih dahulu pengecekan terhadap otorisasi akun, sebelum dapat menggunakan function ini.
        */
        try {
            //pertama-tama akan dipastikan bahwa client sudah memasukkan auth-token
            $token_decoded = $this->auth_token($this->request->getHeader('auth-token'));
            //jika pengguna belum memasukkan auth-token, maka akan muncul pesan seperti diatas.
            if (!$token_decoded) {
                $response = [
                    'status' => 401,
                    'error' => true,
                    'message' => 'auth-token must be set as header request',
                ];
            } else {
                $level = $token_decoded->data->acc_level;
                //Jika ternyata yang mengakses function ini bukan pengguna  (level user) ataupun admin, 
                if ($level != "admin" && $level != "user") {
                    $response = [
                        'status' => 403,
                        'error' => true,
                        // maka akan ditampilkan pesan berikut.
                        'message' => 'Current account does not have permission to create comment',
                    ];
                } else {
                    //kemudian dipastikan bahwa auth-token yang digunakan masih berlaku
                    if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                        // rules dan message adalah validasi dan pesan validasi terhadap input yang akan diberikan.
                        $rules = [
                            "content" => "required",
                            "id_article" => "required", // Validasi Exist Id article
                        ];

                        $messages = [
                            "content" => [
                                "required" => "Content is required"
                            ],
                            "id_article" => [
                                "required" => "ID of article is required"
                            ]
                        ];

                        //Jika input yang diberikan tidak sesuai dengan validasi kami, maka akan ditampilkan pesan error.
                        if (!$this->validate($rules, $messages)) {
                            $response = [
                                'status' => 400,
                                'error' => true,
                                'messages' => $this->validator->getErrors(),
                            ];
                        } else {
                            //Pada input ID article, dilakukan validasi, 
                            $id_article = $this->request->getVar("id_article");
                            $is_exist = $this->articleModel->where('id', $id_article)->findAll();
                            
                            // bahwa jika ID article yang dimasukkan tidak terdapat pada database, 
                            if (!$is_exist) {
                                $response = [
                                    'status' => 404,
                                    'error' => false,
                                    // maka akan muncul pesan seperti berikut.
                                    'message' => "ID of article: '{$id_article}' does not exist",
                                ];
                            } else {
                                $data = [
                                    "id_account" => $token_decoded->data->acc_id,
                                    "id_article" => $this->request->getVar("id_article"),
                                    "content" => $this->request->getVar("content"),
                                ];

                                //Jika data sudah sesuai dengan validasi yang ada, 
                                if ($this->commentModel->insert($data)) {
                                    $is_exist = $this->articleModel->where('id', $id_article)->first();
                                    $title = $is_exist['title'];

                                    // maka komentar akan dimasukkan kedalam database dan diberikan respon berikut
                                    $response = [
                                        'status' => 201,
                                        'error' => false,
                                        // kemudian akan muncul pesan seperti berikut.
                                        'message' => "Comment created on article: '$title'",
                                        'id_comment' => $this->commentModel->getInsertID(),
                                    ];
                                } else {
                                    $response = [
                                        'status' => 500,
                                        'error' => true,
                                        'message' => 'Internal server error, please try again later',
                                    ];
                                }
                            }
                        }
                    } else {
                        //Jika ternyata auth-token yang digunakan sudah tidak berlaku lagi, 
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

    // DELETE -> /comment/$id
    //function ini berguna untuk menghapus komentar
    public function delete($id = null)
    {
        /*
        Untuk menghapus komentar kami hanya memperbolehkan admin, pemilik komentar, ataupun pemilik artikel yang dapat menghapus komentar.
        Kami juga memastikan bahwa pengguna umum tidak dapat menghapus komentar.
        */
        try {
            //Disini kami memastikan bahwa client memasukkan auth-token
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

                //kemudian dipastikan bahwa auth-token yang digunakan masih berlaku
                if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                    //kemudian dilakukan pengecekan apakah komentar dengan ID yang dimasukkan terdapat pada database.
                    $comment = $this->commentModel->where('id', $id)->first();

                    if ($comment) {
                        $level = $token_decoded->data->acc_level;
                        $article = $this->articleModel->where('id', $comment['id_article'])->first();
                        // Lalu dilakukan validasi, apakah yang akan menghapus komentar ini adalah admin, 
                        // pemilik komentar, atau author artikel.
                        if ($level != "admin" && $comment['id_account'] != $token_decoded->data->acc_id && $article['id_account'] != $token_decoded->data->acc_id) {
                            $response = [
                                'status' => 403,
                                'error' => true,
                                // Jika ternyata tidak, maka akan ditampilkan pesan seperti berikut.
                                'message' => 'Current account does not have permission to delete comment',
                            ];
                            
                            
                        } else {
                            //Jika semua validasi sudah terlewati dengan benar, 
                            // maka komentar dengan ID yang dimasukkan akan dihapus 
                            if ($this->commentModel->delete($id)) {
                                $response = [
                                    'status' => 200,
                                    'error' => false,
                                    // lalu akan ditampilkan pesan seperti berikut.
                                    'message' => "Comment based on ID: '$id' has been deleted",
                                ];
                            } else {
                            //Jika ternyata proses penghapusan terjadi masalah
                            $response = [
                                'status' => 500,
                                'error' => true,
                                // maka akan ditampilkan pesan seperti berikut.
                                'message' => "Internal server error, please try again later",
                                ];
                            }
                        }
                    } else {
                        //Jika ID komentar yang dimasukkan tidak ada pada database
                        $response = [
                            'status' => 404,
                            'error' => false,
                            // maka akan ditampilkan komentar seperti berikut.
                            'message' => "Comment based on ID: '{$id}' is not found"
                        ];
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
