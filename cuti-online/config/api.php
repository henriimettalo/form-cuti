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
        'employees:profile' => [
            'label' => 'Baca profil sensitif pegawai',
            'description' => 'Melihat NIK, NPWP, NIP pasangan, dan rekening pegawai melalui API.',
        ],
        'income-records:read' => [
            'label' => 'Baca riwayat gaji pokok',
            'description' => 'Melihat catatan dan riwayat gaji pokok melalui API.',
        ],
        'income-records:write' => [
            'label' => 'Tulis riwayat gaji pokok',
            'description' => 'Menambahkan catatan gaji pokok melalui API.',
        ],
        'leave-requests:read' => [
            'label' => 'Baca formulir cuti',
            'description' => 'Melihat daftar dan detail formulir cuti melalui API.',
        ],
        'ceremony-schedules:read' => [
            'label' => 'Baca jadwal apel',
            'description' => 'Melihat jadwal apel dan upacara melalui API.',
        ],
        'counter-duty-schedules:read' => [
            'label' => 'Baca jadwal piket',
            'description' => 'Melihat daftar petugas piket loket melalui API.',
        ],
    ],

    'pagination' => [
        'default' => 25,
        'max' => 100,
    ],
];
