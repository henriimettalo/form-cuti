<?php

return [
    'abilities' => [
        'employees:read' => [
            'label' => 'Baca data pegawai',
            'description' => 'Melihat daftar dan profil pegawai melalui API.',
        ],
        'employees:write' => [
            'label' => 'Tambah data pegawai',
            'description' => 'Menambahkan pegawai baru melalui API.',
        ],
        'leave-requests:read' => [
            'label' => 'Baca formulir cuti',
            'description' => 'Melihat daftar dan detail formulir cuti melalui API.',
        ],
    ],

    'pagination' => [
        'default' => 25,
        'max' => 100,
    ],
];
