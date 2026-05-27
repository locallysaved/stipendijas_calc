<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atzīmju importēšana</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
<div class="max-w-7xl mx-auto">

    <h1 class="text-2xl font-bold mb-6">XLSX → MySQL imports</h1>

    {{-- Flash message --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-6">
            {{ session('error') }}
        </div>
    @endif

    {{-- Upload form --}}
    <div class="bg-white rounded shadow p-6 mb-8">
        <h2 class="text-lg font-semibold mb-4">Augšupielādēt failus</h2>
        <form action="{{ route('xlsx.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Priekšmetu fails (.xlsx)
                    </label>
                    <input type="file" name="subjects_file" accept=".xlsx,.xls"
                           class="border rounded px-3 py-2 text-sm w-full">
                    @error('subjects_file')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Atzīmju fails (.xlsx)
                    </label>
                    <input type="file" name="grades_file" accept=".xlsx,.xls"
                           class="border rounded px-3 py-2 text-sm w-full">
                    @error('grades_file')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm">
                Importēt
            </button>
        </form>
    </div>

    {{-- Grades table --}}
    <div class="bg-white rounded shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Atzīmes ({{ $grades->total() }})</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="border px-3 py-2">Priekšmets</th>
                        <th class="border px-3 py-2">Inf. tips</th>
                        <th class="border px-3 py-2">Inf. veids</th>
                        <th class="border px-3 py-2">Priekšm. veids</th>
                        <th class="border px-3 py-2">Datums</th>
                        <th class="border px-3 py-2">Vērt. tips</th>
                        <th class="border px-3 py-2">Īlens</th>
                        <th class="border px-3 py-2">Kārkliņš</th>
                        <th class="border px-3 py-2">Varizeja</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grades as $grade)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-3 py-2 font-medium">
                            {{ $grade->subject?->macibu_prieksments ?? '—' }}
                        </td>
                        <td class="border px-3 py-2">{{ $grade->informacijas_tips ?? '—' }}</td>
                        <td class="border px-3 py-2">{{ $grade->informacijas_veids ?? '—' }}</td>
                        <td class="border px-3 py-2">{{ $grade->prieksmetu_veids ?? '—' }}</td>
                        <td class="border px-3 py-2">{{ $grade->datums?->format('d.m.Y') ?? '—' }}</td>
                        <td class="border px-3 py-2">{{ $grade->vertejuma_tips ?? '—' }}</td>
                        <td class="border px-3 py-2">{{ $grade->ilens ?? '—' }}</td>
                        <td class="border px-3 py-2">{{ $grade->karklins ?? '—' }}</td>
                        <td class="border px-3 py-2">{{ $grade->varizeja ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="border px-3 py-6 text-center text-gray-400">
                            Nav datu. Augšupielādē failus augstāk.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $grades->links() }}</div>
    </div>

</div>
</body>
</html>