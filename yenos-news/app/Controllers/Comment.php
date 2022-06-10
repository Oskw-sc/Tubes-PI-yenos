<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\UserModel;
use App\Models\CommentModel;
//memanggil model kategori, artikel, dan komentar

use CodeIgniter\RESTful\ResourceController;
//memanggil resource controller agar routing dapat berjalan

use Exception;

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
//memanggil fungsi jwt untuk penggunaan token

class Comment extends ResourceController
{

    function __construct()
    {
        $this->commentModel = new CommentModel();
        $this->articleModel = new ArticleModel();
        $this->userModel = new UserModel();
    }
    // function diatas bekerja sebagai construct, untuk memanggil model komentar, kategori, dan artikel agar pada function berikutnya dapat langsung digunakan.

    //function dibawah adalah fungsi yang akan melakukan pengecekan terhadap auth-token yang digunakan oleh client
    private function auth_token($auth_token_header)
    {
        if ($auth_token_header) {
            $key = getenv('JWT_SECRET');
            $auth_token_value = $auth_token_header->getValue();
            return JWT::decode($auth_token_value, new Key($key, 'HS256'));
        } else return null;
    }

    //function ini berguna untuk menampilkan komentar
    public function index()
    {
        //Ada beberapa cara dalam mencari komentar pada API kami.
        try {
            $keyword = $this->request->getVar('keyword');
            if (isset($keyword)) $this->commentModel->like('content', $keyword);
            //cara pertama adalah menggunakan keyword. Pengguna hanya perlu menuliskan keyword yang ingin dicari, maka akan ditampilkan komentar yang berisikan kata-kata dari keyword yang di inputkan.

            $id_article = $this->request->getVar('id_article');
            if (isset($id_article)) $this->commentModel->where('id_article', $id_article);
            //cara kedua adalah dengan mencari berdasarkan ID dari artikel. Sehingga jika pengguna ingin mengetahui komentar apa saja yang ada pada artikel tertentu, maka cara ini dapat digunakan.

            $data = $this->commentModel->orderBy('id', 'DESC')->findAll();
            //tetapi jika pengguna tidak memasukkan input keyword ataupun id_article, maka secara default akan ditampilkan seluruh komentar yang ada pada database.

            if ($data) {
                $response = [
                    'status' => 200,
                    'error' => false,
                    'message' => 'Retrieve comment(s) succeed',
                    'data' => $data
                ];
                //Jika data komentar yang dicari ternyata ada pada database, maka akan ditampilkan pesan seperti diatas.
            } else {
                $response = [
                    'status' => 404,
                    'error' => false,
                    'message' => 'Comment(s) based on query parameter(s) is not found',
                ];
                //Tetapi jika data dari komentar yang dicari tidak ada pada database, maka akan ditampilkan seperti diatas.
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

    //function ini berguna untuk menampilkan komentar berdasarkan ID
    public function show($id = null)
    {
        //pertama-tama akan dicari data ID komentar didalam database.
        $data = $this->commentModel->where('id', $id)->findAll();

        if ($data) {
            $data_array = $this->commentModel->where('id', $id)->first();
            $id_article = $data_array['id_article'];

            $is_exist = $this->articleModel->where('id', $id_article)->first();

            $data_detail = [
                "id" => $data_array['id'],
                "id_account" => $data_array['id_account'],
                "id_article" => $id_article,
                "article_title" => $is_exist['title'],
                "comment" => $data_array['content'],
            ];
            return $this->respond($data_detail, 200);
            //jika ternyata data tersebut ada pada database, akan ditampilkan pesan seperti diatas.
        } else {
            return $this->failNotFound("Cannot found article by id : $id");
            //jika ID komentar tidak terdapat pada database, akan ditampilkan pesan seperti diatas.
        }
    }

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
            if (!$token_decoded) {
                $response = [
                    'status' => 401,
                    'error' => true,
                    'message' => 'auth-token must be set as header request',
                ];
                //jika pengguna belum memasukkan auth-token, maka akan muncul pesan seperti diatas.
            } else {
                $level = $token_decoded->data->acc_level;
                if ($level != "admin" && $level != "user") {
                    $response = [
                        'status' => 403,
                        'error' => true,
                        'message' => 'Current account does not have permission to create comment',
                    ];
                    //Jika ternyata yang mengakses function ini bukan pengguna  (level user) ataupun admin, maka akan ditampilkan pesan seperti diatas.
                } else {
                    //kemudian dipastikan bahwa auth-token yang digunakan masih berlaku
                    if ($token_decoded && ($token_decoded->exp - time() > 0)) {
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
                        // rules dan message adalah validasi dan pesan validasi terhadap input yang akan diberikan.

                        if (!$this->validate($rules, $messages)) {
                            $response = [
                                'status' => 400,
                                'error' => true,
                                'messages' => $this->validator->getErrors(),
                            ];
                            //Jika input yang diberikan tidak sesuai dengan validasi kami, maka akan ditampilkan pesan error.
                        } else {
                            $id_article = $this->request->getVar("id_article");
                            $is_exist = $this->articleModel->where('id', $id_article)->findAll();

                            if (!$is_exist) {
                                $response = [
                                    'status' => 404,
                                    'error' => false,
                                    'message' => "ID of article: '{$id_article}' does not exist",
                                ];
                                //Pada input ID article, dilakukan validasi, bahwa jika ID article yang dimasukkan tidak terdapat pada database, maka akan muncul pesan seperti diatas.
                            } else {
                                $data = [
                                    "id_account" => $token_decoded->data->acc_id,
                                    "id_article" => $this->request->getVar("id_article"),
                                    "content" => $this->request->getVar("content"),
                                ];

                                if ($this->commentModel->insert($data)) {
                                    $is_exist = $this->articleModel->where('id', $id_article)->first();
                                    $title = $is_exist['title'];

                                    $response = [
                                        'status' => 201,
                                        'error' => false,
                                        'message' => "Comment created on article: '$title'",
                                        'id_comment' => $this->commentModel->getInsertID(),
                                    ];
                                    //Jika data sudah sesuai dengan validasi yang ada, maka komentar akan dimasukkan kedalam database dan kemudian akan muncul pesan seperti diatas.
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
            if (!$token_decoded) {
                $response = [
                    'status' => 401,
                    'error' => true,
                    'message' => 'auth-token must be set as header request',
                ];
                //jika pengguna belum memasukkan auth-token, maka akan muncul pesan seperti diatas.
            } else {

                //kemudian dipastikan bahwa auth-token yang digunakan masih berlaku
                if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                    $comment = $this->commentModel->where('id', $id)->first();
                    //kemudian dilakukan pengecekan apakah komentar dengan ID yang dimasukkan terdapat pada database.

                    if ($comment) {
                        $level = $token_decoded->data->acc_level;
                        $article = $this->articleModel->where('id', $comment['id_article'])->first();
                        if ($level != "admin" && $comment['id_account'] != $token_decoded->data->acc_id && $article['id_account'] != $token_decoded->data->acc_id) {
                            $response = [
                                'status' => 403,
                                'error' => true,
                                'message' => 'Current account does not have permission to delete comment',
                            ];
                            /*
                            Lalu dilakukan validasi, apakah yang akan menghapus komentar ini adalah admin, pemilik komentar, atau author artikel.
                            Jika ternyata tidak, maka akan ditampilkan pesan seperti diatas.
                            */
                        } else {
                            if ($this->commentModel->delete($id)) {
                                $response = [
                                    'status' => 200,
                                    'error' => false,
                                    'message' => "Comment based on ID: '$id' has been deleted",
                                ];
                                //Jika semua validasi sudah terlewati dengan benar, maka komentar dengan ID yang dimasukkan akan dihapus, dan akan tampil pesan seperti diatas.
                            } else {
                                $response = [
                                    'status' => 500,
                                    'error' => true,
                                    'message' => "Internal server error, please try again later",
                                ];
                                //Jika ternyata proses penghapusan terjadi masalah, akan ditampilkan pesan seperti diatas.
                            }
                        }
                    } else {
                        $response = [
                            'status' => 404,
                            'error' => false,
                            'message' => "Comment based on ID: '{$id}' is not found"
                        ];
                        //Jika ID komentar yang dimasukkan tidak ada pada database, maka akan ditampilkan komentar seperti diatas.
                    }
                } else {
                    $response = [
                        'status' => 401,
                        'error' => true,
                        'message' => 'auth-token is invalid, might be expired',
                    ];
                    //Jika auth-token yang diberikan ternyata sudah tidak berlaku, akan ditampilkan pesan diatas.
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
}
