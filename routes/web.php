<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

if (! function_exists('statusUsiaHewan')) {
    function statusUsiaHewan(int $usia_tahun): string
    {
        if ($usia_tahun >= 3) {
            return 'Dewasa';
        }

        return 'Muda';
    }
}

Route::get('/pasien-hewan', function (Request $request) {
    $kataKunci = trim(strip_tags((string) $request->query('q', '')));
    $masterId = trim((string) $request->query('jenis_hewan_id', ''));

    try {
        $pdo = DB::connection()->getPdo();

        $sql = 'SELECT t.id, t.kode_pasien, t.nama_hewan, t.nomor_rekam,
                       t.usia_tahun, t.jenis_hewan_id, m.nama_jenis
                FROM pasien_hewan AS t
                JOIN jenis_hewan AS m
                    ON m.id = t.jenis_hewan_id
                WHERE 1 = 1';
        $parameter = [];

        if ($kataKunci !== '') {
            $sql .= ' AND (t.kode_pasien LIKE :kata_kunci_kode
                        OR t.nama_hewan LIKE :kata_kunci_nama
                        OR t.nomor_rekam LIKE :kata_kunci_u2)';
            $nilaiPencarian = '%' . $kataKunci . '%';
            $parameter['kata_kunci_kode'] = $nilaiPencarian;
            $parameter['kata_kunci_nama'] = $nilaiPencarian;
            $parameter['kata_kunci_u2'] = $nilaiPencarian;
        }

        if ($masterId !== '') {
            $sql .= ' AND t.jenis_hewan_id = :master_id';
            $parameter['master_id'] = (int) $masterId;
        }

        $sql .= ' ORDER BY t.kode_pasien';

        $ambilData = $pdo->prepare($sql);
        $ambilData->execute($parameter);
        $daftarData = $ambilData->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($daftarData as $indeks => $baris) {
            $daftarData[$indeks]['status'] = statusUsiaHewan((int) $baris['usia_tahun']);
        }

        $ambilMaster = $pdo->prepare(
            'SELECT id, nama_jenis
             FROM jenis_hewan
             ORDER BY nama_jenis'
        );
        $ambilMaster->execute();
        $daftarMaster = $ambilMaster->fetchAll(\PDO::FETCH_ASSOC);

        return view('pasien-hewan', compact(
            'daftarData',
            'daftarMaster',
            'kataKunci',
            'masterId'
        ));
    } catch (\Throwable $error) {
        report($error);

        return response(
            'Data tidak dapat dibaca. Periksa koneksi dan query.',
            500
        );
    }
})->name('pasien-hewan.index');

Route::post('/pasien-hewan', function (Request $request) {
    $request->merge([
        'kode_pasien' => strip_tags(trim((string) $request->input('kode_pasien'))),
        'nama_hewan' => strip_tags(trim((string) $request->input('nama_hewan'))),
        'nomor_rekam' => strip_tags(trim((string) $request->input('nomor_rekam'))),
    ]);

    $data = $request->validate([
        'kode_pasien' => ['required', 'alpha_num:ascii', 'size:10'],
        'nama_hewan' => ['required', 'string', 'min:2', 'max:100'],
        'nomor_rekam' => ['required', 'string', 'min:5', 'max:30', 'regex:/^[A-Za-z0-9\-]+$/'],
        'usia_tahun' => ['required', 'integer', 'min:0', 'max:30'],
        'jenis_hewan_id' => ['required', 'integer'],
    ], [
        'kode_pasien.required' => 'Kode pasien wajib diisi.',
        'kode_pasien.alpha_num' => 'Kode pasien hanya boleh berisi huruf dan angka.',
        'kode_pasien.size' => 'Kode pasien harus tepat 10 karakter.',
        'nama_hewan.required' => 'Nama hewan wajib diisi.',
        'nama_hewan.min' => 'Nama hewan minimal 2 karakter.',
        'nama_hewan.max' => 'Nama hewan maksimal 100 karakter.',
        'nomor_rekam.required' => 'Nomor rekam wajib diisi.',
        'nomor_rekam.regex' => 'Nomor rekam hanya boleh berisi huruf, angka, dan tanda hubung.',
        'nomor_rekam.min' => 'Nomor rekam minimal 5 karakter.',
        'nomor_rekam.max' => 'Nomor rekam maksimal 30 karakter.',
        'usia_tahun.required' => 'Usia (tahun) wajib diisi.',
        'usia_tahun.integer' => 'Usia (tahun) harus berupa bilangan bulat.',
        'usia_tahun.min' => 'Usia (tahun) minimal 0.',
        'usia_tahun.max' => 'Usia (tahun) maksimal 30.',
        'jenis_hewan_id.required' => 'Jenis hewan wajib dipilih.',
        'jenis_hewan_id.integer' => 'Jenis hewan tidak valid.',
    ]);

    try {
        $pdo = DB::connection()->getPdo();

        $cekMaster = $pdo->prepare(
            'SELECT COUNT(*) FROM jenis_hewan WHERE id = :id'
        );
        $cekMaster->execute(['id' => $data['jenis_hewan_id']]);

        if ((int) $cekMaster->fetchColumn() === 0) {
            return back()->withInput()->with(
                'gagal',
                'Jenis hewan tidak tersedia.'
            );
        }

        $cekDuplikat = $pdo->prepare(
            'SELECT COUNT(*) FROM pasien_hewan
             WHERE kode_pasien = :kode OR nomor_rekam = :u2'
        );
        $cekDuplikat->execute([
            'kode' => $data['kode_pasien'],
            'u2' => $data['nomor_rekam'],
        ]);

        if ((int) $cekDuplikat->fetchColumn() > 0) {
            return back()->withInput()->with(
                'gagal',
                'Kode pasien atau Nomor rekam sudah digunakan.'
            );
        }

        $simpan = $pdo->prepare(
            'INSERT INTO pasien_hewan
                (kode_pasien, nama_hewan, nomor_rekam, usia_tahun, jenis_hewan_id)
             VALUES (:kode, :nama, :u2, :angka, :master_id)'
        );
        $simpan->execute([
            'kode' => $data['kode_pasien'],
            'nama' => $data['nama_hewan'],
            'u2' => $data['nomor_rekam'],
            'angka' => $data['usia_tahun'],
            'master_id' => $data['jenis_hewan_id'],
        ]);

        return redirect()->route('pasien-hewan.index')->with(
            'berhasil',
            'Data berhasil ditambahkan.'
        );
    } catch (\Throwable $error) {
        report($error);

        return back()->withInput()->with(
            'gagal',
            'Data gagal disimpan. Periksa koneksi dan data.'
        );
    }
})->name('pasien-hewan.store');


Route::get('/pasien-hewan/{id}/edit', function (int $id) {
    try {
        $pdo = DB::connection()->getPdo();

        $ambilData = $pdo->prepare(
            'SELECT id, kode_pasien, nama_hewan, nomor_rekam, usia_tahun, jenis_hewan_id
             FROM pasien_hewan WHERE id = :id'
        );
        $ambilData->execute(['id' => $id]);
        $dataEdit = $ambilData->fetch(\PDO::FETCH_ASSOC);

        if (! $dataEdit) {
            return redirect()->route('pasien-hewan.index')->with(
                'gagal',
                'Data tidak ditemukan.'
            );
        }

        $ambilMaster = $pdo->prepare(
            'SELECT id, nama_jenis FROM jenis_hewan ORDER BY nama_jenis'
        );
        $ambilMaster->execute();
        $daftarMaster = $ambilMaster->fetchAll(\PDO::FETCH_ASSOC);

        return view('pasien-hewan-edit', compact('dataEdit', 'daftarMaster'));
    } catch (\Throwable $error) {
        report($error);

        return redirect()->route('pasien-hewan.index')->with(
            'gagal',
            'Data gagal dibuka untuk diubah.'
        );
    }
})->whereNumber('id')->name('pasien-hewan.edit');

Route::put('/pasien-hewan/{id}', function (Request $request, int $id) {
    $request->merge([
        'kode_pasien' => strip_tags(trim((string) $request->input('kode_pasien'))),
        'nama_hewan' => strip_tags(trim((string) $request->input('nama_hewan'))),
        'nomor_rekam' => strip_tags(trim((string) $request->input('nomor_rekam'))),
    ]);

    $data = $request->validate([
        'kode_pasien' => ['required', 'alpha_num:ascii', 'size:10'],
        'nama_hewan' => ['required', 'string', 'min:2', 'max:100'],
        'nomor_rekam' => ['required', 'string', 'min:5', 'max:30', 'regex:/^[A-Za-z0-9\-]+$/'],
        'usia_tahun' => ['required', 'integer', 'min:0', 'max:30'],
        'jenis_hewan_id' => ['required', 'integer'],
    ], [
        'kode_pasien.required' => 'Kode pasien wajib diisi.',
        'kode_pasien.alpha_num' => 'Kode pasien hanya boleh berisi huruf dan angka.',
        'kode_pasien.size' => 'Kode pasien harus tepat 10 karakter.',
        'nama_hewan.required' => 'Nama hewan wajib diisi.',
        'nama_hewan.min' => 'Nama hewan minimal 2 karakter.',
        'nama_hewan.max' => 'Nama hewan maksimal 100 karakter.',
        'nomor_rekam.required' => 'Nomor rekam wajib diisi.',
        'nomor_rekam.regex' => 'Nomor rekam hanya boleh berisi huruf, angka, dan tanda hubung.',
        'nomor_rekam.min' => 'Nomor rekam minimal 5 karakter.',
        'nomor_rekam.max' => 'Nomor rekam maksimal 30 karakter.',
        'usia_tahun.required' => 'Usia (tahun) wajib diisi.',
        'usia_tahun.integer' => 'Usia (tahun) harus berupa bilangan bulat.',
        'usia_tahun.min' => 'Usia (tahun) minimal 0.',
        'usia_tahun.max' => 'Usia (tahun) maksimal 30.',
        'jenis_hewan_id.required' => 'Jenis hewan wajib dipilih.',
        'jenis_hewan_id.integer' => 'Jenis hewan tidak valid.',
    ]);

    try {
        $pdo = DB::connection()->getPdo();

        $cekAda = $pdo->prepare(
            'SELECT COUNT(*) FROM pasien_hewan WHERE id = :id'
        );
        $cekAda->execute(['id' => $id]);

        if ((int) $cekAda->fetchColumn() === 0) {
            return redirect()->route('pasien-hewan.index')->with(
                'gagal',
                'Data tidak ditemukan.'
            );
        }

        $cekMaster = $pdo->prepare(
            'SELECT COUNT(*) FROM jenis_hewan WHERE id = :id'
        );
        $cekMaster->execute(['id' => $data['jenis_hewan_id']]);

        if ((int) $cekMaster->fetchColumn() === 0) {
            return back()->withInput()->with(
                'gagal',
                'Jenis hewan tidak tersedia.'
            );
        }

        $cek = $pdo->prepare(
            'SELECT COUNT(*) FROM pasien_hewan
             WHERE (kode_pasien = :kode OR nomor_rekam = :u2)
               AND id <> :id'
        );
        $cek->execute([
            'kode' => $data['kode_pasien'],
            'u2' => $data['nomor_rekam'],
            'id' => $id,
        ]);

        if ((int) $cek->fetchColumn() > 0) {
            return back()->withInput()->with(
                'gagal',
                'Kode pasien atau Nomor rekam sudah digunakan data lain.'
            );
        }

        $ubah = $pdo->prepare(
            'UPDATE pasien_hewan
             SET kode_pasien = :kode, nama_hewan = :nama, nomor_rekam = :u2,
                 usia_tahun = :angka, jenis_hewan_id = :master_id
             WHERE id = :id'
        );
        $ubah->execute([
            'kode' => $data['kode_pasien'],
            'nama' => $data['nama_hewan'],
            'u2' => $data['nomor_rekam'],
            'angka' => $data['usia_tahun'],
            'master_id' => $data['jenis_hewan_id'],
            'id' => $id,
        ]);

        return redirect()->route('pasien-hewan.index')->with(
            'berhasil',
            'Data berhasil diperbarui.'
        );
    } catch (\Throwable $error) {
        report($error);

        return back()->withInput()->with(
            'gagal',
            'Data gagal diperbarui. Periksa koneksi dan data.'
        );
    }
})->whereNumber('id')->name('pasien-hewan.update');
//delete
Route::delete('/pasien-hewan/{id}', function (int $id) {
    try {
        $pdo = DB::connection()->getPdo();
        $hapus = $pdo->prepare(
            'DELETE FROM pasien_hewan WHERE id = :id'
        );
        $hapus->execute(['id' => $id]);

        if ($hapus->rowCount() === 0) {
            return redirect()->route('pasien-hewan.index')->with(
                'gagal',
                'Data tidak ditemukan.'
            );
        }

        return redirect()->route('pasien-hewan.index')->with(
            'berhasil',
            'Data berhasil dihapus.'
        );
    } catch (\Throwable $error) {
        report($error);

        return redirect()->route('pasien-hewan.index')->with(
            'gagal',
            'Data gagal dihapus. Periksa relasi atau koneksi.'
        );
    }
})->whereNumber('id')->name('pasien-hewan.destroy');
