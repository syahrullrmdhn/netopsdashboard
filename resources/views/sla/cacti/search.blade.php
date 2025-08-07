@extends('layouts.app')

@section('title','SLA by Cacti Interface')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6 mt-8">
    <form method="GET" action="{{ route('sla.cacti.export') }}">
        <div class="mb-4">
            <label class="block mb-1 font-semibold text-gray-700">Cari Interface (device/interface):</label>
            <input type="text" id="search_interface" class="w-full border px-3 py-2 rounded" autocomplete="off" placeholder="Ketik nama interface/device...">
            <input type="hidden" name="interface_id" id="interface_id">
            <div id="search_results" class="absolute bg-white border w-full z-10"></div>
        </div>
        <div class="flex gap-2 mb-4">
            <div class="flex-1">
                <label class="block mb-1 font-semibold text-gray-700">Date From</label>
                <input type="date" name="date_from" value="{{ $date_from }}" class="w-full border px-3 py-2 rounded">
            </div>
            <div class="flex-1">
                <label class="block mb-1 font-semibold text-gray-700">Date To</label>
                <input type="date" name="date_to" value="{{ $date_to }}" class="w-full border px-3 py-2 rounded">
            </div>
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-semibold w-full mt-2">Export SLA Excel</button>
    </form>
</div>

<script>
let timeout, resultsDiv = document.getElementById('search_results');
document.getElementById('search_interface').addEventListener('input', function() {
    clearTimeout(timeout);
    let val = this.value;
    if (val.length < 2) { resultsDiv.innerHTML = ''; return; }
    timeout = setTimeout(() => {
        fetch('/sla-cacti/search-interface?q=' + encodeURIComponent(val))
        .then(res => res.json())
        .then(data => {
            resultsDiv.innerHTML = '';
            data.forEach(row => {
                let el = document.createElement('div');
                el.className = 'px-3 py-2 hover:bg-blue-100 cursor-pointer';
                el.textContent = row.label;
                el.onclick = () => {
                    document.getElementById('search_interface').value = row.label;
                    document.getElementById('interface_id').value = row.id;
                    resultsDiv.innerHTML = '';
                }
                resultsDiv.appendChild(el);
            });
        });
    }, 250);
});
</script>
@endsection
