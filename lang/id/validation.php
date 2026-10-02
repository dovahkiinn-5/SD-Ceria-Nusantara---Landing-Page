<?php
return [
    'required'=>':attribute wajib diisi.', 'string'=>':attribute harus berupa teks.', 'email'=>':attribute harus berupa alamat email yang valid.',
    'date'=>':attribute harus berupa tanggal yang valid.', 'before'=>':attribute harus sebelum :date.', 'after'=>':attribute harus setelah :date.',
    'after_or_equal'=>':attribute harus tanggal :date atau sesudahnya.', 'in'=>'Pilihan :attribute tidak valid.',
    'max'=>['string'=>':attribute maksimal :max karakter.','file'=>':attribute maksimal :max KB.','array'=>':attribute terlalu banyak.'],
    'min'=>['string'=>':attribute minimal :min karakter.'], 'accepted'=>'Persetujuan :attribute wajib diberikan.',
    'mimes'=>'Format :attribute harus :values.', 'file'=>':attribute harus berupa berkas.', 'uploaded'=>':attribute gagal diunggah. Periksa ukuran berkas.',
    'regex'=>'Format :attribute tidak valid.', 'confirmed'=>'Konfirmasi :attribute tidak sama.', 'array'=>':attribute tidak valid.',
    'attributes'=>[
        'child_name'=>'nama lengkap anak','nickname'=>'nama panggilan','birth_date'=>'tanggal lahir','gender'=>'jenis kelamin',
        'parent_name'=>'nama orang tua / wali','relationship'=>'hubungan dengan anak','phone'=>'nomor WhatsApp','email'=>'email',
        'birth_certificate'=>'akta kelahiran','family_card'=>'Kartu Keluarga','photo'=>'pas foto','consent'=>'kebijakan privasi',
        'name'=>'nama','date'=>'tanggal kunjungan','password'=>'kata sandi',
    ],
];
