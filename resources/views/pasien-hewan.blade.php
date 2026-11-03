<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pasien Hewan</title>
</head>
<body>
    <h1>Data Pasien Hewan</h1>

    @if (session('berhasil'))
        <p>{{ session('berhasil') }}</p>
    @endif

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

    <h2>Tambah Pasien Hewan</h2>
    <form action="{{ route('pasien-hewan.store') }}" method="POST">
        @csrf
        <p>Kode pasien<br><input type="text" name="kode_pasien" value="{{ old('kode_pasien') }}"></p>
        <p>Nama hewan<br><input type="text" name="nama_hewan" value="{{ old('nama_hewan') }}"></p>
        <p>Nomor rekam<br><input type="text" name="nomor_rekam" value="{{ old('nomor_rekam') }}"></p>
        <p>Usia (tahun)<br><input type="number" name="usia_tahun" value="{{ old('usia_tahun') }}"></p>
        <p>Jenis hewan<br>
            <select name="jenis_hewan_id">
                <option value="">-- Pilih Jenis hewan --</option>
                @foreach ($daftarMaster as $master)
                    <option value="{{ $master['id'] }}"
                        @selected(old('jenis_hewan_id') == $master['id'])>
                        {{ $master['nama_jenis'] }}
                    </option>
                @endforeach
            </select>
        </p>
        <button type="submit">Tambah Data</button>
    </form>

    <h2>Pencarian dan Filter</h2>
    <form action="{{ route('pasien-hewan.index') }}" method="GET">
        <p>Kata Kunci<br>
            <input type="text" name="q" value="{{ $kataKunci }}">
        </p>
        <p>Jenis hewan<br>
            <select name="jenis_hewan_id">
                <option value="">-- Semua --</option>
                @foreach ($daftarMaster as $master)
                    <option value="{{ $master['id'] }}"
                        @selected((string) $masterId === (string) $master['id'])>
                        {{ $master['nama_jenis'] }}
                    </option>
                @endforeach
            </select>
        </p>
        <button type="submit">Cari</button>
        <a href="{{ route('pasien-hewan.index') }}">Reset</a>
    </form>

    <h2>Daftar Pasien Hewan</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <tr>
            <th>Kode pasien</th><th>Nama hewan</th><th>Nomor rekam</th>
            <th>Usia (tahun)</th><th>Jenis hewan</th><th>Status</th><th>Aksi</th>
        </tr>
        @forelse ($daftarData as $item)
            <tr>
                <td>{{ $item['kode_pasien'] }}</td>
                <td>{{ $item['nama_hewan'] }}</td>
                <td>{{ $item['nomor_rekam'] }}</td>
                <td>{{ $item['usia_tahun'] }}</td>
                <td>{{ $item['nama_jenis'] }}</td>
                <td>
                    @if ($item['status'] === 'Dewasa')
                        {{ $item['status'] }}
                    @else
                        {{ $item['status'] }}
                    @endif
                </td>
                <td>
                    <a href="{{ route('pasien-hewan.edit', $item['id']) }}">
                        Ubah
                    </a>
                    <form action="{{ route('pasien-hewan.destroy', $item['id']) }}"
                          method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                onclick="return confirm('Hapus data ini?')">
                            Hapus
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">Data tidak ditemukan.</td></tr>
        @endforelse
    </table>
</body>
</html>
