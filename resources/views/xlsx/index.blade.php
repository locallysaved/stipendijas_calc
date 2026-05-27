<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imports</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white h-screen flex items-center justify-center">
    <form action="{{ route('xlsx.results') }}" class="flex flex-col items-center gap-8">
        @csrf

        <div class="flex flex-col gap-4 w-72">
            <label class="flex flex-col gap-2">
                <span class="text-xs font-medium tracking-widest text-gray-400 uppercase">Subjects file</span>
                <input type="file" name="subjects_file" accept=".xlsx,.xls"
                    class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:tracking-wide file:bg-gray-100 file:text-gray-600 hover:file:bg-gray-200 cursor-pointer">
            </label>

            <label class="flex flex-col gap-2">
                <span class="text-xs font-medium tracking-widest text-gray-400 uppercase">Grades file</span>
                <input type="file" name="grades_file" accept=".xlsx,.xls"
                    class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:tracking-wide file:bg-gray-100 file:text-gray-600 hover:file:bg-gray-200 cursor-pointer">
            </label>
        </div>

        <button type="submit"
            class="mt-2 px-10 py-3 bg-gray-900 text-white text-xs font-semibold tracking-widest uppercase rounded-full hover:bg-gray-700 transition-colors duration-200">
            Import
        </button>
    </form>
</body>
</html>