{{-- resources/views/tickets/rfo.blade.php --}}
<div class="space-y-8 p-6 max-w-4xl mx-auto bg-white rounded-lg shadow-sm">
  {{-- Company Header --}}
  <div class="flex justify-between items-center border-b pb-4 mb-6">
    <div>
      <div class="flex items-center gap-3">
        {{-- Logo yang sudah diubah --}}
        <img src="{{ asset('images/android-chrome-192x192.png') }}" alt="Company Logo" class="h-12 w-12 object-contain">
        <div>
          <h2 class="text-xl font-bold text-gray-900">PT. Abhinawa Sumberdaya Asia</h2>
          <p class="text-sm text-gray-500">Div. Network Operations Center</p>
        </div>
      </div>
    </div>
    <div class="text-right">
      <div class="text-sm font-medium text-gray-700">IR Created</div>
      <div class="text-xs text-gray-500">{{ now()->format('d F Y H:i') }}</div>
    </div>
  </div>

  {{-- Report Title --}}
  <div class="text-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Post Incident Report</h1>
    <div class="mt-2 text-sm text-gray-500">
      Incident Ticket {{ $ticket->ticket_number }}
    </div>
  </div>

  {{-- Ticket Information --}}
  <div class="border-b pb-4 mb-4">
    <h2 class="text-lg font-semibold text-gray-700 mb-3">Incident Information</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <dt class="text-sm font-medium text-gray-500">Open Date</dt>
        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->open_date}}</dd>
      </div>
      <div>
        <dt class="text-sm font-medium text-gray-500">Customer</dt>
        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->customer->customer }} ({{ $ticket->customer->cid_abh }})</dd>
      </div>
      <div>
        <dt class="text-sm font-medium text-gray-500">ABH Ticket</dt>
        <dd class="mt-1 text-sm font-mono text-gray-900">{{ $ticket->ticket_number }}</dd>
      </div>
      <div>
        <dt class="text-sm font-medium text-gray-500">Type of Issue</dt>
        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->issue_type }}</dd>
      </div>
      <div>
        <dt class="text-sm font-medium text-gray-500">Start Time</dt>
        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->start_time}}</dd>
      </div>
      <div>
        <dt class="text-sm font-medium text-gray-500">End Time</dt>
        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->end_time}}</dd>
      </div>
      <div>
        <dt class="text-sm font-medium text-gray-500">MTTR</dt>
        <dd class="mt-1 text-sm text-gray-900">
          @if($ticket->end_time && $ticket->open_date)
              {{$ticket->end_time->format('d/m/Y H:i') }} - {{$ticket->open_date->format('d/m/Y H:i') }}
              ({{ $ticket->end_time->diffInMinutes($ticket->open_date)}} minutes)
          @else
          Ongoing
          @endif
      </div>
      <div>
        <dt class="text-sm font-medium text-gray-500">Duration</dt>
        <dd class="mt-1 text-sm text-gray-900">
          @if($ticket->start_time && $ticket->end_time)
            {{ $ticket->start_time->format('d/m/Y H:i') }} - {{ $ticket->end_time->format('d/m/Y H:i') }}
            ({{ $ticket->end_time->diffInMinutes($ticket->start_time) }} minutes)
          @else
            Ongoing
          @endif
        </dd>
      </div>
    </div>
  </div>

  {{-- Main Content Sections --}}
  <div class="space-y-6">
    {{-- Root Cause --}}
    <div class="border-b pb-4">
      <h3 class="text-lg font-semibold text-gray-700 mb-2">Root Cause Analysis</h3>
      <div class="prose prose-sm max-w-none">
        <p class="whitespace-pre-line text-gray-800">{{ $ticket->problem_detail }}</p>
      </div>
    </div>

    {{-- Action Taken --}}
    <div class="border-b pb-4">
      <h3 class="text-lg font-semibold text-gray-700 mb-2">Corrective Actions</h3>
      <div class="prose prose-sm max-w-none">
        <p class="whitespace-pre-line text-gray-800">{{ $ticket->action_taken }}</p>
      </div>
    </div>

    {{-- Preventive/Improvement --}}
    <div class="border-b pb-4">
      <h3 class="text-lg font-semibold text-gray-700 mb-2">Preventive & Improvement</h3>
      <div class="prose prose-sm max-w-none">
        <p class="whitespace-pre-line text-gray-800">{{ $ticket->preventive_action }}</p>
      </div>
    </div>

    {{-- Chronology --}}
    <div>
      <h3 class="text-lg font-semibold text-gray-700 mb-3">Incident Chronology</h3>
      <ul class="space-y-4">
        @foreach($ticket->updates as $u)
          <li class="pl-4 border-l-2 border-blue-200">
            <div class="flex justify-between text-xs text-gray-500">
              <span>{{ $u->created_at->format('d/m/Y H:i') }}</span>
              <span>by Abhinawa NOC</span>
            </div>
            <p class="mt-1 text-sm text-gray-800">{{ $u->detail ?? 'No Detail Provided' }}</p>
          </li>
        @endforeach
      </ul>
    </div>
  </div>

  {{-- Footer --}}
  <div class="pt-6 mt-6 border-t text-xs text-gray-500 text-center">
    <p>This report was automatically generated by Abhinawa Network Operations Center</p>
    <p class="mt-1">© {{ now()->year }} PT Abhinawa Sumberdaya Asia. All rights reserved.</p>
  </div>
</div>
