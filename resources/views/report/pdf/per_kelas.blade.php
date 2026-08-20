<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pelanggaran Per Kelas</title>
    <style>
        @page { margin: 18mm 12mm 18mm 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10px; color: #334155; line-height: 1.5; background: #fff; }

        .page-break { page-break-after: always; }
        .no-break { page-break-inside: avoid; }

        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #4f46e5; padding-bottom: 12px; position: relative; }
        .header img { height: 60px; vertical-align: middle; }
        .header-text { display: inline-block; vertical-align: middle; text-align: center; margin-left: 15px; }
        .header h1 { font-size: 18px; color: #1e1b4b; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 2px; }
        .header h2 { font-size: 12px; color: #4f46e5; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .header p { font-size: 10px; color: #64748b; font-weight: 500; }

        .kelas-title { background: #4f46e5; color: white; padding: 10px 16px; border-radius: 4px; font-size: 14px; font-weight: 700; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .kelas-info { font-size: 10px; color: #e0e7ff; font-weight: 400; margin-left: 10px; opacity: 0.9; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border-radius: 4px; overflow: hidden; }
        th { background: #f8fafc; color: #475569; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 8px 10px; border: 1px solid #e2e8f0; text-align: left; }
        td { padding: 6px 10px; border: 1px solid #e2e8f0; font-size: 9px; vertical-align: top; color: #334155; }
        tr:nth-child(even) td { background: #fcfdfe; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }

        .summary-box { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 4px; padding: 12px 20px; margin-top: 10px; }
        .summary-item { display: inline-block; margin-right: 40px; }
        .summary-label { font-size: 8px; color: #64748b; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 2px; }
        .summary-value { font-size: 16px; font-weight: 800; color: #0f172a; }

        .footer { text-align: center; font-size: 8px; color: #94a3b8; margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-style: italic; }

        .pelanggaran-list { font-size: 9px; color: #475569; line-height: 1.4; }
        .pelanggaran-list span { display: block; padding: 1px 0; }

        .poin-badge { background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 999px; font-weight: 700; font-size: 9px; border: 1px solid #fecaca; }
    </style>
</head>
<body>
    @foreach ($kelasData as $index => $kelas)
    <div class="{{ !$loop->last ? 'page-break' : '' }}">
        {{-- Header --}}
        <div class="header">
            @if(file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Logo">
            @endif
            <div class="header-text">
                <h1>SMAN 21 SURABAYA</h1>
                <h2>Laporan Pelanggaran Siswa — Per Kelas</h2>
                <p>{{ $semesterLabel }}</p>
            </div>
        </div>

        {{-- Kelas Title --}}
        <div class="kelas-title">
            Kelas {{ $kelas->kelas }}
            <span class="kelas-info">{{ $kelas->murid->count() }} siswa · {{ $kelas->total_records }} pelanggaran · Total {{ $kelas->total_poin }} poin</span>
        </div>

        {{-- Table --}}
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">No</th>
                    <th style="width: 22%;">Nama Murid</th>
                    <th style="width: 8%;" class="text-center">NIS</th>
                    <th style="width: 5%;" class="text-center">L/P</th>
                    <th style="width: 35%;">Pelanggaran</th>
                    <th style="width: 10%;" class="text-center">Jumlah</th>
                    <th style="width: 15%;" class="text-center">Total Poin</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kelas->murid as $i => $murid)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="font-bold">{{ $murid->nama_murid }}</td>
                    <td class="text-center">{{ $murid->NIS }}</td>
                    <td class="text-center">{{ $murid->gender === 'Laki-laki' ? 'L' : 'P' }}</td>
                    <td>
                        <div class="pelanggaran-list">
                            @forelse ($murid->pelanggaran as $p)
                                <span>• {{ $p->jenis_pelanggaran }} ({{ \Carbon\Carbon::parse($p->tanggal)->format('d/m/Y') }}@if($p->waktu) {{ \Carbon\Carbon::parse($p->waktu)->format('H:i') }}@endif) - {{ $p->pelapor }}</span>
                            @empty
                                <span style="color: #9ca3af; font-style: italic;">— tidak ada —</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="text-center">{{ $murid->jumlah }}x</td>
                    <td class="text-center">
                        <span class="poin-badge" style="{{ $murid->total_poin === 0 ? 'background: #f0fdf4; color: #16a34a;' : '' }}">
                            {{ $murid->total_poin }} poin
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #9ca3af;">Tidak ada data pelanggaran</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Summary --}}
        <div class="summary-box">
            <div class="summary-item">
                <div class="summary-label">Total Siswa</div>
                <div class="summary-value">{{ $kelas->murid->count() }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Pelanggaran</div>
                <div class="summary-value">{{ $kelas->total_records }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Poin</div>
                <div class="summary-value">{{ $kelas->total_poin }}</div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            Dicetak pada {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }} · Next Generation Student Discipline System - SMAN 21 Surabaya
        </div>
    </div>
    @endforeach
</body>
</html>
