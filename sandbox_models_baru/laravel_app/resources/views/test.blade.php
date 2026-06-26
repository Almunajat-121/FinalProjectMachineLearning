<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sandbox: Uji Model IndoBERT Baru</title>
    <!-- Tailwind CSS (CDN for quick testing) -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-900">

    <div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Sandbox: Uji Model IndoBERT Baru</h1>
            <p class="text-gray-600 mb-6">Masukkan teks keluhan untuk melihat hasil prediksi (Kategori & Urgensi) dari model yang ada di folder <code>models_baru</code>.</p>

            @if($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <!-- Heroicon name: solid/x-circle -->
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                {{ $errors->first() }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('analyze') }}" method="POST" class="mb-8">
                @csrf
                <div class="mb-4">
                    <label for="text" class="block text-sm font-medium text-gray-700 mb-2">Teks Keluhan/Aduan (Min 15 Karakter)</label>
                    <textarea id="text" name="text" rows="4" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md p-3" placeholder="Contoh: AC di ruang kelas 101 mati sehingga sangat panas saat perkuliahan berlangsung. Mohon segera diperbaiki." required>{{ old('text') }}</textarea>
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Analisis Teks
                </button>
            </form>

            @if(session('result'))
                @php $res = session('result'); @endphp
                <div class="border-t border-gray-200 pt-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Hasil Analisis</h2>
                    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Kategori Prediksi</h3>
                                <div class="mt-1 flex items-center">
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                        {{ $res['category'] }}
                                    </span>
                                    <span class="ml-2 text-sm text-gray-500">
                                        (Confidence: {{ $res['confidence']['category_score'] }})
                                    </span>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Tingkat Urgensi</h3>
                                <div class="mt-1 flex items-center">
                                    @php
                                        $urgColor = 'bg-green-100 text-green-800';
                                        if($res['urgency'] == 'SEDANG') $urgColor = 'bg-yellow-100 text-yellow-800';
                                        if($res['urgency'] == 'TINGGI') $urgColor = 'bg-orange-100 text-orange-800';
                                        if($res['urgency'] == 'KRITIS') $urgColor = 'bg-red-100 text-red-800';
                                    @endphp
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full {{ $urgColor }}">
                                        {{ $res['urgency'] }}
                                    </span>
                                    <span class="ml-2 text-sm text-gray-500">
                                        (Confidence: {{ $res['confidence']['urgency_score'] }})
                                    </span>
                                </div>
                            </div>

                        </div>

                        <div class="mt-6">
                            <h3 class="text-sm font-medium text-gray-500">Kata Kunci Terekstrak</h3>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($res['keywords_extracted'] as $kw)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-gray-200 text-gray-800">
                                        {{ $kw }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-6 text-xs text-gray-400 font-mono">
                            <p>Ticket ID: {{ $res['ticket_id'] }} | Inference Time: {{ $res['inference_ms'] }}ms | Mode: {{ $res['mode'] }}</p>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</body>
</html>
