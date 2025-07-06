<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Report Sensor {{ $sensorId }} ({{ $days }}d)</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size:12px }
    h1 { font-size:16px; margin-bottom:0.5rem }
    table { width:100%; border-collapse: collapse; margin-top:1rem }
    th,td { border:1px solid #444; padding:4px; }
    th { background:#eee; }
  </style>
</head>
<body>
  <h1>{{ $sensorName }} (ID: {{ $sensorId }}) ‒ Last {{ $days }} days</h1>
  <p>Period: {{ $sdate }} … {{ $edate }}</p>
  <table>
    <thead>
      <tr>
        <th style="width:5%">No</th>
        <th style="width:35%">Datetime</th>
        <th style="width:20%">Value</th>
      </tr>
    </thead>
    <tbody>
      @foreach($historic as $i => $d)
      <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $d['datetime'] }}</td>
        <td>{{ $d['value'] }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
