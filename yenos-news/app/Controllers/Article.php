<?php

namespace App\Controllers;

// Memanggil model kategori, artikel, dan komentar
use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\CommentModel;

// Memanggil resource controller agar routing dapat berjalan
use CodeIgniter\RESTful\ResourceController;

use Exception;
// Memanggil fungsi jwt untuk penggunaan token
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class Article extends ResourceController
{
    // Membuat fungsi untuk memanggil model komentar, kategori, dan artikel agar pada function berikutnya dapat langsung digunakan.
    function __construct()
    {
        $this->articleModel = new ArticleModel();
        $this->categoryModel = new CategoryModel();
        $this->commentModel = new CommentModel();
        
        $db = \Config\Database::connect();
        $this->articleDetailView = $db->table('article_details');
    }

    // Membuat function yang akan melakukan pengecekan terhadap auth-token yang digunakan oleh client
    private function auth_token($auth_token_header)
    {
        if ($auth_token_header) {
            $key = getenv('JWT_SECRET');
            $auth_token_value = $auth_token_header->getValue();
            return JWT::decode($auth_token_value, new Key($key, 'HS256'));
        } else return null;
    }

    // GET -> /article
    // GET -> /article?id_category=&status=&keyword=

    // Kode ini bertujuan untuk mendapatkan daftar artikel yang ada.
    // Ada juga fitur search untuk mencari artikel yang terkait dengan keyword yang di masukkan pengguna.
    public function index()
    {
        try {
            $this->articleDetailView->select('id_article, title, cover, status, id_category, category, updated_at');
            $keyword = $this->request->getVar('keyword');
            if (isset($keyword)) $this->articleDetailView->like('title', $keyword);
            $categoryID = $this->request->getVar('id_category');
            if (isset($categoryID)) $this->articleDetailView->where('id_category', $categoryID);
            $status = in_array($this->request->getVar('status'), ['active', 'non-active']) ? $this->request->getVar('status') : null;
            if ($status) $this->articleDetailView->where('status', $status);
            $data = $this->articleDetailView->orderBy('id_article', 'DESC')->get()->getResult();

            // jika terdapat artikel yang harus ditampilkan
            if (count($data) > 0) {
                $response = [
                    'status' => 200,
                    'error' => false,
                    // maka akan ditampilkan pesan seperti berikut.
                    'message' => 'Retrieve list succeed',
                    'data' => $data
                ];
            // jika ternyata tidak ada list yang dapat ditampilkan
            } else {
                $response = [
                    'status' => 404,
                    'error' => false,
                    // maka akan ditampilkan pesan seperti berikut.
                    'message' => 'List of article based on query parameter(s) is empty',
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

    // GET -> /artikel/$id
    // Kode ini bertujuan untuk mendapatkan artikel tertentu berdasarkan ID serta menampilkan komentar yang ada pada artikel.
    public function show($id = null)
    {
        try {
            // pertama-tama dilakukan pencari data artikel berdasarkan ID yang dimasukkan oleh client.
            $data = $this->articleDetailView->where('id_article', $id)->get()->getResult();
            
            //jika ID kategori terdapat tidak ada pada database
            if (!$data) {
                $response = [
                    'status' => 404,
                    'error' => false,
                    // maka akan ditampilkan pesan seperti berikut.
                    'message' => "Article based on ID: '{$id}' is not found",
                ];
            } else {
                $this->commentModel->select('comments.id as id_comment, comments.content, comments.id_account, accounts.name as commentator');
                $this->commentModel->join('accounts', 'accounts.id = comments.id_account');
                $comments = $this->commentModel->where('id_article', $id)->orderBy('id_comment', 'DESC')->findAll();
                $comment_count = count($comments);
                //jika ID kategori terdapat pada database
                $data[0]->comments = $comments;
                $data[0]->comment_count = $comment_count;
                
                $response = [
                    'status' => 200,
                    'error' => false,
                    // maka akan ditampilkan pesan seperti berikut.
                    'message' => "Article based on ID: '{$id}' is found",
                    'data' => $data[0],
                ];
            }
        } catch (Exception $ex) {
            $response = [
                'status' => 500,
                'error' => true,
                'message' => 'Internal server error, please try again later',
            ];
        }

        return $this->respond($response, $response['status']);
    }

    // POST -> /artikel
    // Kode ini bertujuan untuk membuat artikel baru dan hanya dapat dilakukan oleh pengguna dengan akun.
    public function create()
    {   
        // mengecek apakah pengguna sudah memasukkan auth-token JWT kedalam request header.
        try {
            $token_decoded = $this->auth_token($this->request->getHeader('auth-token'));
            // jika pengguna belum memasukkan auth-token JWT
            if (!$token_decoded) {
                $response = [
                    'status' => 401,
                    'error' => true,
                    // maka akan ditampilkan pesan seperti berikut.
                    'message' => 'auth-token must be set as header request',
                ];
            } else {
                // mengecek apakah level pengguna admin atau user.
                $level = $token_decoded->data->acc_level;
                // jika pengguna bukan admin atau pun user
                if($level != "admin" && $level != "user") {
                    $response = [
                        'status' => 403,
                        'error' => true,
                        // maka akan ditampilkan pesan seperti berikut.
                        'message' => 'Current account does not have permission to create article',
                    ];
                } else {
                    if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                        // rules dan message adalah validasi dan pesan validasi terhadap input yang akan diberikan.
                        $rules = [
                            "title" => "required|max_length[300]",
                            "cover_link" => "required|max_length[300]|valid_url",
                            "content" => "required",
                            "id_category" => "required",
                        ];
                        $messages = [
                            "title" => [
                                "required" => "Title is required",
                                "max_length" => "Title can be filled by a maximum of 300 characters",
                            ],
                            "cover_link" => [
                                "required" => "Cover link is required",
                                "max_length" => "Cover link can be filled by a maximum of 300 characters",
                                "valid_url" => "Cover link must be filled by valid URL",
                            ],
                            "content" => [
                                "required" => "Content is required"
                            ],
                            "id_category" => [
                                "required" => "ID of category is required"
                            ]
                        ];

                        //Jika input yang diberikan tidak sesuai dengan validasi kami
                        if (!$this->validate($rules, $messages)) {
                            $response = [
                                'status' => 400,
                                'error' => true,
                                // maka akan ditampilkan pesan seperti berikut.
                                'messages' => $this->validator->getErrors(),
                            ];
                        } else {
                            $id_category = $this->request->getVar("id_category");
                            $is_exist = $this->categoryModel->where('id', $id_category)->findAll();
                            if (!$is_exist) {
                                $response = [
                                    'status' => 404,
                                    'error' => false,
                                    'message' => "ID of category: '{$id_category}' does not exist",
                                ];
                            //Jika input sudah sesuai dengan validasi yang ada, 
                            } else {
                                $data = [
                                    "id_account" => $token_decoded->data->acc_id,
                                    "id_category" => $this->request->getVar("id_category"),
                                    "title" => $this->request->getVar("title"),
                                    "cover" => $this->request->getVar("cover_link"),
                                    "description" => $this->request->getVar("content")
                                ];
                                
                                if ($this->articleModel->insert($data)) {
                                    // maka data akan dimasukkan kedalam database
                                    $response = [
                                        'status' => 201,
                                        'error' => false,
                                        // maka akan ditampilkan pesan seperti berikut.
                                        'message' => 'Article has been created successfully',
                                        'id_article' => $this->articleModel->getInsertID()
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
                    // Jika ternyata auth-token yang digunakan sudah tidak berlaku lagi
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

        // setiap pesan response yang akan diberikan, direturn kembali ke function melalui kode diatas.
        return $this->respond($response, $response['status']);
    }

    // PUT -> /artikel/$id
    // PATCH -> /artikel/$id
    // Kode ini bertujuan untuk menyunting artikel yang telah ada dan dapat dilakukan oleh akun author(user yang membuat artikel) atau admin.
    // Pada Put harus memberikan jumlah parameter yang sesuai sebagai request body.
    // Pada Patch minimal 1 parameter dikirim sebagai request body. 
    public function update($id = null)
    {   
        // mengecek apakah pengguna sudah memasukkan auth-token JWT kedalam request header.
        try {
            $token_decoded = $this->auth_token($this->request->getHeader('auth-token'));
            // jika pengguna belum memasukkan auth-token JWT
            if (!$token_decoded) {
                $response = [
                    'status' => 401,
                    'error' => true,
                    // maka akan ditampilkan pesan seperti berikut.
                    'message' => 'auth-token must be set as header request',
                ];
            } else {
                // Jika token valid, maka cek apakah artikel yang dimasukkan ada dalam database atau tidak.
                if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                    $dataExist = $this->articleModel->where('id', $id)->first();
                    // mengecek apakah level pengguna user atau admin. 
                    // Jika bukan admin dan bukan merupakan artikel yang dibuat author(user), maka buat validasi.
                    if ($dataExist) {
                        $level = $token_decoded->data->acc_level;
                        // jika pengguna bukan admin dan id nya tidak ada dalam database
                        if ($level != "admin" && $dataExist['id_account'] != $token_decoded->data->acc_id) {
                            $response = [
                                'status' => 403,
                                'error' => true,
                                // maka akan ditampilkan pesan seperti berikut.
                                'message' => 'Current account does not have permission to update or edit this article',
                            ];
                        } else {
                            $input = $this->request->getRawInput();
                            // jika pengguna bukan admin dan pengguna menginput status
                            if ($level != "admin" && isset($input['status'])) {
                                $response = [
                                    'status' => 403,
                                    'error' => true,
                                    // maka akan ditampilkan pesan seperti berikut.
                                    'message' => 'Current account does not have permission to update or edit status of this article',
                                ];
                            } else {
                                // penggunaan switch jika case put dan patch.
                                switch ($this->request->getMethod()) {
                                    case 'put':
                                        // rules dan message adalah validasi dan pesan validasi terhadap input yang diberikan.
                                        $rules = [
                                            "title" => "required|max_length[300]",
                                            "cover_link" => "required|max_length[300]|valid_url",
                                            "content" => "required",
                                            "id_category" => "required",
                                            "status" => "required|in_list[active,non-active]",
                                        ];

                                        $messages = [
                                            "title" => [
                                                "required" => "Title is required",
                                                "max_length" => "Title can be filled by a maximum of 300 characters",
                                            ],
                                            "cover_link" => [
                                                "required" => "Cover link is required",
                                                "max_length" => "Cover link can be filled by a maximum of 300 characters",
                                                "valid_url" => "Cover link must be filled by valid URL",
                                            ],
                                            "content" => [
                                                "required" => "Content is required"
                                            ],
                                            "id_category" => [
                                                "required" => "ID Category is required"
                                            ],
                                            "status" => [
                                                "required" => "Status is required",
                                                'in_list' => "Status must be filled between 'active' or 'non-active'",
                                            ],
                                        ];
                                        //Jika ternyata input yang diberikan tidak sesuai dengan validasi yang ada
                                        if (!$this->validate($rules, $messages)) {
                                            $response = [
                                                'status' => 400,
                                                'error' => true,
                                                // maka akan ditampilkan pesan error bedasarkan validasi yang tidak sesuai.                            
                                                'messages' => $this->validator->getErrors()
                                            ];  
                                        } else {
                                            // Mengecek jika ada inputan id category dan cek apakah id category ada dalam database atau tidak. 
                                            $id_category = $input['id_category'];
                                            $categoryExist = $this->categoryModel->where('id', $id_category)->findAll();
                                            // jika id category tidak ada dalam database
                                            if (!$categoryExist) {
                                                $response = [
                                                    'status' => 404,
                                                    'error' => false,
                                                    // maka akan ditampilkan pesan seperti berikut.
                                                    'message' => "ID of category: '{$id_category}' does not exist"
                                                ];
                                            } else {
                                                $data = [
                                                    "id_category" => $input["id_category"],
                                                    "title" => $input["title"],
                                                    "cover" => $input["cover_link"],
                                                    "description" => $input["content"],
                                                    "status" => $input["status"],
                                                ];
                                                // Jika input sudah sesuai dengan validasi yang ada maka data akan diupdate ke database, 
                                                if ($this->articleModel->update($id, $data)) {
                                                    $response = [
                                                        'status' => 200,
                                                        'error' => false,
                                                        // kemudian akan ditampilkan pesan seperti berikut.
                                                        'message' => "Article based on ID: '$id' has been updated",
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
                                        break;
                                        case 'patch':
                                            // Pada kode ini, berfungsi untuk mengecek apakah ada inputan dari pengguna.
                                            // Jika ada inputan, maka validasi dan berikan pesan jika tidak sesuai dengan rules yang diberikan.
                                            if (isset($input['title']) || isset($input['cover_link']) || isset($input['content']) || isset($input['id_category']) || isset($input['status'])) {
                                                if (isset($input['title'])) {
                                                    // rules dan message adalah validasi dan pesan validasi terhadap input yang diberikan.
                                                    $rules['title'] = 'required|max_length[300]';
                                                    $messages['title'] = [
                                                        "required" => "Title is required",
                                                    "max_length" => "Title can be filled by a maximum of 300 characters",
                                                ];
                                            }
                                            if (isset($input['cover_link'])) {
                                                $rules['cover_link'] = 'required|max_length[300]|valid_url';
                                                $messages['cover_link'] = [
                                                    "required" => "Cover link is required",
                                                    "max_length" => "Cover link can be filled by a maximum of 300 characters",
                                                    "valid_url" => "Cover link must be filled by valid URL",
                                                ];
                                            }
                                            if (isset($input['content'])) {
                                                $rules['content'] = 'required';
                                                $messages['content'] = [
                                                    "required" => "Content is required"
                                                ];
                                            }
                                            if (isset($input['id_category'])) {
                                                $rules['id_category'] = 'required';
                                                $messages['id_category'] = [
                                                    "required" => "ID Category is required"
                                                ];
                                                
                                            }
                                            if (isset($input['status'])) {
                                                $rules['status'] = 'required|in_list[active,non-active]';
                                                $messages['status'] = [
                                                    "required" => "Status is required",
                                                    'in_list' => "Status must be filled between 'active' or 'non-active'",
                                                ];
                                            }

                                            // Jika ternyata input yang diberikan tidak sesuai dengan validasi yang ada 
                                            if (!$this->validate($rules, $messages)) {
                                                $response = [
                                                    'status' => 400,
                                                    'error' => true,
                                                    // maka akan ditampilkan pesan error bedasarkan validasi yang tidak sesuai.
                                                    'messages' => $this->validator->getErrors()
                                                ];
                                            } else {
                                                // Jika ada inputan dari category, 
                                                // maka cek apakah id category tersebut ada didatabase atau tidak. 
                                                if (isset($input['id_category'])) {
                                                    $id_category = $input['id_category'];
                                                    $categoryExist = $this->categoryModel->where('id', $id_category)->findAll();
                                                    // jika id category tidak ada dalam database
                                                    if (!$categoryExist) {
                                                        $response = [
                                                            'status' => 404,
                                                            'error' => false,
                                                            // maka akan ditampilkan pesan seperti berikut.
                                                            'message' => "ID of category: '{$id_category}' does not exist"
                                                        ];
                                                        break;
                                                    }
                                                }
                                                // Set inputan dan update ke dalam database.
                                                if (isset($input['id_category'])) $this->articleModel->set('id_category', $input['id_category']);
                                                if (isset($input['title'])) $this->articleModel->set('title', $input['title']);
                                                if (isset($input['cover_link'])) $this->articleModel->set('cover', $input['cover_link']);
                                                if (isset($input['content'])) $this->articleModel->set('description', $input['content']);
                                                if (isset($input['status'])) $this->articleModel->set('status', $input['status']);
                                                $this->articleModel->where('id', $id);

                                                // Jika update berhasil
                                                if ($this->articleModel->update()) {
                                                    $response = [
                                                        'status' => 200,
                                                        'error' => false,
                                                        // maka akan ditampilkan pesan seperti berikut.
                                                        'message' => "Article based on ID: '$id' has been edited",
                                                    ];
                                                // Jika update gagal dilakukan
                                                } else {
                                                    $response = [
                                                        'status' => 500,
                                                        'error' => true,
                                                        // maka akan ditampilkan pesan seperti berikut.
                                                        'message' => 'Internal server error, please try again later',
                                                    ];
                                                }
                                            }
                                        // Jika parameter yang diberikan tidak ada
                                        } else {
                                            $response = [
                                                'status' => 400,
                                                'error' => true,
                                                // maka akan ditampilkan pesan seperti berikut.
                                                'message' => 'Either title, cover_link, content, id_category, or status must be sent as body request to edit article'
                                            ];
                                        }
                                    break;
                                    // Jika metode yang diminta tidak sesuai
                                    default:
                                        $response = [
                                            'status' => 405,
                                            'error' => true,
                                            // maka akan ditampilkan pesan seperti berikut.
                                            'message' => 'This kind of method request is not accepted',
                                        ];
                                    break;
                                };
                            }
                        }
                    // jika id artikel yang dimasukkan tidak ada dalam database
                    } else {
                        $response = [
                            'status' => 404,
                            'error' => false,
                            // maka akan ditampilkan pesan seperti berikut.
                            'message' => "Article based on ID: '{$id}' is not found"
                        ];
                    }
            // Jika ternyata auth-token yang digunakan sudah tidak berlaku lagi
            } else {
                $response = [
                    'status' => 401,
                    'error' => true,
                    // maka akan ditampilkan pesan seperti berikut.
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

        // setiap pesan response yang akan diberikan, direturn kembali ke function melalui kode diatas.
        return $this->respond($response, $response['status']);
    }

    // DEL -> /article/$id
    // Kode ini bertujuan untuk menghapus artikel yang telah ada 
    // dan hanya dapat dilakukan oleh akun author(user yang membuat artikel) atau admin.
    public function delete($id = null)
    {
        // mengecek apakah pengguna sudah memasukkan auth-token JWT kedalam request header.
        try {
            $token_decoded = $this->auth_token($this->request->getHeader('auth-token'));
            // Jika pengguna belum memasukkan auth-token JWT
            if (!$token_decoded) {
                $response = [
                    'status' => 401,
                    'error' => true,
                    // maka akan ditampilkan pesan seperti berikut.
                    'message' => 'auth-token must be set as header request',
                ];
            } else {
                // Jika token valid, maka cek apakah artikel yang dimasukkan ada dalam database atau tidak.
                if ($token_decoded && ($token_decoded->exp - time() > 0)) {
                    $data = $this->articleModel->where('id', $id)->first();
                    if ($data) {
                        // mengecek apakah level pengguna user atau admin. 
                        // Jika bukan admin dan bukan merupakan artikel yang dibuat author(user), maka buat validasi.
                        $level = $token_decoded->data->acc_level;
                        // jika pengguna bukan admin dan id nya tidak ada dalam database
                        if ($level != "admin" && $data['id_account'] != $token_decoded->data->acc_id) {
                            $response = [
                                'status' => 403,
                                'error' => true,
                                // maka akan ditampilkan pesan seperti berikut.
                                'message' => 'Current account does not have permission to delete this article',
                            ];
                        } else {
                            // jika article terhapus dalam database
                            if ($this->articleModel->delete($id)) {
                                $response = [
                                    'status' => 200,
                                    'error' => false,
                                    // maka akan ditampilkan pesan seperti berikut.
                                    'message' => "Article and its comment(s) based on ID: '$id' has been deleted",
                                ];
                            } else {
                                $response = [
                                    'status' => 500,
                                    'error' => true,
                                    'message' => "Internal server error, please try again later",
                                ];
                            }
                        }
                    // jika id artikel yang dimasukkan tidak ada dalam database
                    } else {
                        $response = [
                            'status' => 404,
                            'error' => false,
                            // maka akan ditampilkan pesan seperti berikut.
                            'message' => "Article based on ID: '{$id}' is not found"
                        ];
                    }
                // Jika ternyata auth-token yang digunakan sudah tidak berlaku lagi
                } else {
                    $response = [
                        'status' => 401,
                        'error' => true,
                        // maka akan ditampilkan pesan seperti berikut.
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

        // setiap pesan response yang akan diberikan, direturn kembali ke function melalui kode diatas.
        return $this->respond($response, $response['status']);
    }
}
