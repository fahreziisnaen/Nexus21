<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pelanggaran Per Murid</title>
    <style>
        @page { margin: 18mm 12mm 18mm 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10px; color: #334155; line-height: 1.5; background: #fff; }

        .page-break { page-break-after: always; }
        .no-break { page-break-inside: avoid; }
        .student-page { height: 100%; position: relative; page-break-after: always; }
        .student-page:last-child { page-break-after: auto; }

        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #4f46e5; padding-bottom: 12px; }
        .header img { height: 60px; vertical-align: middle; }
        .header-text { display: inline-block; vertical-align: middle; text-align: center; margin-left: 15px; }
        .header h1 { font-size: 18px; color: #1e1b4b; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 2px; }
        .header h2 { font-size: 12px; color: #4f46e5; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .header p { font-size: 10px; color: #64748b; font-weight: 500; }

        .student-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 20px; background: #fff; }
        .student-info { display: table; width: 100%; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }
        .student-info .col { display: table-cell; width: 50%; vertical-align: top; }
        .info-row { margin-bottom: 6px; }
        .info-label { font-size: 8px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .info-value { font-size: 12px; font-weight: 700; color: #1e293b; }

        .penanganan-box { border-radius: 6px; padding: 10px 15px; margin-bottom: 12px; border-left: 4px solid; }
        .penanganan-title { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 3px; }
        .penanganan-label { font-size: 12px; font-weight: 800; }
        .penanganan-desc { font-size: 9px; margin-top: 4px; line-height: 1.4; color: #475569; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; border-radius: 4px; overflow: hidden; }
        th { background: #f8fafc; color: #475569; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 8px 10px; border: 1px solid #e2e8f0; text-align: left; }
        td { padding: 8px 10px; border: 1px solid #e2e8f0; font-size: 9px; vertical-align: top; color: #334155; }
        tr:nth-child(even) td { background: #fcfdfe; }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }

        .poin-total { font-size: 24px; font-weight: 800; }
        .poin-badge { background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 999px; font-weight: 700; border: 1px solid #fecaca; }

        .footer { text-align: center; font-size: 8px; color: #94a3b8; margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-style: italic; }
    </style>
</head>
<body>
    @foreach ($muridData as $murid)
    <div class="student-page">
        {{-- Header --}}
        <div class="header">
            @if(file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Logo">
            @endif
            <div class="header-text">
                <h1>SMAN 21 SURABAYA</h1>
                <h2>Laporan Pelanggaran Siswa — Detail Per Murid</h2>
                <p>{{ $semesterLabel }}</p>
            </div>
        </div>

        {{-- Student Info --}}
        <div class="student-card">
            <div class="student-info">
                <div class="col">
                    <div class="info-row">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value">{{ $murid->nama_murid }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">NIS</div>
                        <div class="info-value">{{ $murid->NIS }}</div>
                    </div>
                </div>
                <div class="col">
                    <div class="info-row">
                        <div class="info-label">Kelas</div>
                        <div class="info-value">{{ $murid->kelas }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Jenis Kelamin</div>
                        <div class="info-value">{{ $murid->gender }}</div>
                    </div>
                </div>
            </div>

            {{-- Total Poin & Penanganan --}}
            <div style="display: table; width: 100%; margin-bottom: 10px;">
                <div style="display: table-cell; width: 30%; vertical-align: middle; text-align: center;">
                    <div class="info-label">Total Poin</div>
                    <div class="poin-total" style="color: {{ $murid->penanganan->color }};">{{ $murid->total_poin }}</div>
                </div>
                <div style="display: table-cell; width: 70%; vertical-align: middle;">
                    <div class="penanganan-box" style="background: {{ $murid->penanganan->color }}15; border: 1px solid {{ $murid->penanganan->color }};">
                        <div class="penanganan-title" style="color: {{ $murid->penanganan->color }};">Penanganan ({{ $murid->penanganan->level }})</div>
                        <div class="penanganan-label" style="color: {{ $murid->penanganan->color }};">{{ $murid->penanganan->label }}</div>
                        <div class="penanganan-desc" style="color: #4b5563;">{{ $murid->penanganan->desc }}</div>
                    </div>
                </div>
            </div>

            {{-- Detail Table --}}
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;" class="text-center">No</th>
                        <th style="width: 14%;">Tanggal</th>
                        <th style="width: 30%;">Jenis Pelanggaran</th>
                        <th style="width: 8%;" class="text-center">Poin</th>
                        <th style="width: 28%;">Keterangan</th>
                        <th style="width: 15%;">Pelapor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($murid->pelanggaran as $i => $p)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($p->tanggal)->format('d/m/Y') }}</td>
                        <td class="font-bold">{{ $p->jenis_pelanggaran }}</td>
                        <td class="text-center"><span class="poin-badge">{{ $p->poin }}</span></td>
                        <td>{{ $p->keterangan ?? '-' }}</td>
                        <td>{{ $p->pelapor ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 15px; color: #9ca3af;">Tidak ada data pelanggaran untuk semester ini.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right font-bold" style="background: #eef2ff;">Total</td>
                        <td class="text-center font-bold" style="background: #eef2ff;">{{ $murid->total_poin ?? 0 }}</td>
                        <td colspan="2" style="background: #eef2ff;"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="footer">
            Dicetak pada {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }} · Next Generation Student Discipline System - SMAN 21 Surabaya
        </div>
    </div>
    @endforeach

    @if($muridData->isEmpty())
    <div class="header">
        @if(file_exists($logoPath))
            <img src="{{ $logoPath }}" alt="Logo">
        @endif
        <div class="header-text">
            <h1>SMAN 21 SURABAYA</h1>
            <h2>Laporan Pelanggaran Siswa — Detail Per Murid</h2>
            <p>{{ $semesterLabel }}</p>
        </div>
    </div>
    <div style="text-align: center; padding: 40px; color: #9ca3af;">
        <p style="font-size: 14px;">Tidak ada data pelanggaran untuk semester ini.</p>
    </div>
    @endif
</body>
</html>
