<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------
    /** @var array<string, array<string, string>> */
    public array $authRegister = [
        'nim'      => 'required|max_length[50]',
        'nama'     => 'required|max_length[100]',
        'kelas'    => 'required|max_length[50]',
        'semester' => 'required|integer|greater_than_equal_to[1]',
        'email'    => 'required|valid_email|is_unique[users.email]',
        'password' => 'required|min_length[8]',
    ];

    /** @var array<string, array<string, string>> */
    public array $authLogin = [
        'email'    => 'required|valid_email',
        'password' => 'required',
    ];

    public array $forumStore = [
        'nama'        => 'required|max_length[100]',
        'jenis_forum' => 'permit_empty|in_list[publik,privat]',
        'deskripsi'   => 'permit_empty',
    ];

    public array $forumUpdate = [
        'nama'        => 'permit_empty|max_length[100]',
        'jenis_forum' => 'permit_empty|in_list[publik,privat]',
        'deskripsi'   => 'permit_empty',
    ];

    public array $forumJoin = [
        'kode_undangan' => 'required|min_length[6]|max_length[10]',
    ];

    public array $memberUpdate = [
        'allowed_upload' => 'required|in_list[0,1]',
    ];

    public array $taskStore = [
        'judul'         => 'required|max_length[100]',
        'deskripsi'     => 'permit_empty',
        'tenggat_waktu' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'file_url'      => 'permit_empty|valid_url',
    ];

    public array $taskUpdate = [
        'judul'         => 'permit_empty|max_length[100]',
        'deskripsi'     => 'permit_empty',
        'tenggat_waktu' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'status'        => 'permit_empty|in_list[todo,doing,done]',
        'file_url'      => 'permit_empty|valid_url',
    ];

    public array $reminderStore = [
        'title' => 'required|max_length[100]',
        'waktu' => 'required|valid_date[Y-m-d H:i:s]',
    ];

    public array $discussionStore = [
        'isi' => 'required',
    ];

    public array $discussionReply = [
        'isi' => 'required',
    ];

    public array $noteStore = [
        'judul'       => 'required|max_length[100]',
        'kategori'    => 'permit_empty|max_length[50]',
        'mata_kuliah' => 'permit_empty|max_length[100]',
        'deskripsi'   => 'permit_empty',
    ];

    public array $noteUpdate = [
        'judul'       => 'permit_empty|max_length[100]',
        'kategori'    => 'permit_empty|max_length[50]',
        'mata_kuliah' => 'permit_empty|max_length[100]',
        'deskripsi'   => 'permit_empty',
    ];

    public array $mediaStore = [
        'file'     => 'uploaded[file]|max_size[file,20480]',
        'forum_id' => 'required|is_natural_no_zero',
        'note_id'  => 'permit_empty|is_natural',
        'ref_id'   => 'permit_empty|is_natural',
    ];
}
