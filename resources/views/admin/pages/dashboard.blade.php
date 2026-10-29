@extends('layouts.admin')

@section('content')
@include('partials.dashboard.dashboard')
@endsection

@section('scripts')
<script>
(function () {
  const agingLabels = @json($agingLabels);
  const agingValues = @json($agingValues);
  const collected = {{ (float) $collectedAmount }};
  const pending = {{ (float) $collectionPending }};
  const ctxA = document.getElementById('agingChart');
  if (ctxA) {
    new Chart(ctxA, {
      type: 'bar',
      data: {
        labels: agingLabels,
        datasets: [{ label: 'Outstanding', data: agingValues, backgroundColor: ['#2E6FE0','#5B8FE8','#FD7E14','#F0932B','#DC3545','#a52834'], borderRadius: 6 }]
      },
      options: { plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: v => '₹' + (v/100000).toFixed(1) + 'L' } } } }
    });
  }
  const ctxC = document.getElementById('collectionChart');
  if (ctxC) {
    new Chart(ctxC, {
      type: 'doughnut',
      data: { labels: ['Collected', 'Pending vs Target'], datasets: [{ data: [collected, pending], backgroundColor: ['#198754', '#EEF1F6'], borderWidth: 0 }] },
      options: { cutout: '70%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } }
    });
  }
})();
</script>
@endsection
