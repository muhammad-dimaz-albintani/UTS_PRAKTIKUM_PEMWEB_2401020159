<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Pasien Hewan</title>
</head>
<body>
    <h1>Ubah Pasien Hewan</h1>

    @if (session('gagal'))
        <p>{{ session('gagal') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('pasien-hewan.update', $dataEdit['id']) }}"
          method="POST">
        @csrf
        @method('PUT')
        <p>Kode pasien<br>
            <input type="text" name="kode_pasien"
                   value="{{ old('kode_pasien', $dataEdit['kode_pasien']) }}">
        </p>
        <p>Nama hewan<br>
            <input type="text" name="nama_hewan"
                   value="{{ old('nama_hewan', $dataEdit['nama_hewan']) }}">
        </p>
        <p>Nomor rekam<br>
            <input type="text" name="nomor_rekam"
                   value="{{ old('nomor_rekam', $dataEdit['nomor_rekam']) }}">
        </p>
        <p>Usia (tahun)<br>
            <input type="number" name="usia_tahun"
                   value="{{ old('usia_tahun', $dataEdit['usia_tahun']) }}">
        </p>
        <p>Jenis hewan<br>
            <select name="jenis_hewan_id">
                @foreach ($daftarMaster as $master)
                    <option value="{{ $master['id'] }}"
                        @selected(old('jenis_hewan_id', $dataEdit['jenis_hewan_id']) == $master['id'])>
                        {{ $master['nama_jenis'] }}
                    </option>
                @endforeach
            </select>
        </p>
        <button type="submit">Simpan Perubahan</button>
        <a href="{{ route('pasien-hewan.index') }}">Kembali</a>
    </form>
</body>
</html>
