<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Cacti Graphs PDF</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size:10px; }
    h1,h2,h3 { margin:8px 0; }
    .grid { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
    .card{border:1px solid #aaa;padding:8px;width:260px;}
    .card img{width:100%;height:auto;border:1px solid #ccc;}
  </style>
</head>
<body>
  <h1>Cacti Graphs Report</h1>
  <p>Date Range: {{ $start }} → {{ $end }}</p>
  @foreach($trees as $tree)
    <h2>{{ $tree['name'] }}</h2>
    @foreach($tree['leaves'] as $leaf => $graphs)
      <h3>{{ $leaf }}</h3>
      <div class="grid">
        @foreach($graphs as $g)
          <div class="card">
            <div><strong>{{ $g->title_cache ?? $g->graph_title ?? '-' }}</strong></div>
            <img src="https://cacti2nd.abhinawa.com/cacti/graph_image.php?
                       local_graph_id={{ $g->id }}
                       &from={{ \Carbon\Carbon::parse($start)->timestamp }}
                       &to={{ \Carbon\Carbon::parse($end)->timestamp }}">
            <div class="text-xs mt-1">ID: {{ $g->id }} | Host: {{ $g->host_id }}</div>
          </div>
        @endforeach
      </div>
    @endforeach
  @endforeach
</body>
</html>
