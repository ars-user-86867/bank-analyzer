<!DOCTYPE html>
<html>
<head>
    <title>Результаты анализа</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="container mt-5">
    <a href="/" class="btn btn-secondary mb-3">Назад</a>
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="card-title text-center">Аналитика за месяц</h2>
            <div class="row align-items-center mt-4">
                <!-- Левая колонка: Цифры -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <h5 class="text-muted">Доход</h5>
                        <p class="text-success fs-3 fw-bold">{{ number_format($income, 2, '.', ' ') }} ₽</p>
                    </div>
                    <div class="mb-3">
                        <h5 class="text-muted">Расход</h5>
                        <p class="text-danger fs-3 fw-bold">{{ number_format($expense, 2, '.', ' ') }} ₽</p>
                    </div>
                    <div>
                        <h5 class="text-muted">Оборот</h5>
                        <p class="text-primary fs-3 fw-bold">{{ number_format($turnover, 2, '.', ' ') }} ₽</p>
                    </div>
                </div>
                
                <!-- Правая колонка: График -->
                <div class="col-md-6">
                    <canvas id="myChart" style="max-width: 300px; margin: 0 auto;"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-4 mb-4">
        <div class="card-body">
            <h3 class="card-title text-center">Расходы по категориям</h3>
            <div style="max-width: 500px; margin: 0 auto;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <h3>Список операций</h3>
    <table class="table table-striped mt-3">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Категория</th>
                <th>Описание</th>
                <th>Сумма</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
            <tr>
                <td>{{ $t['date'] }}</td>
                <td>{{ $t['category'] }}</td>
                <td>{{ $t['description'] }}</td>
                <td class="{{ $t['amount'] > 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($t['amount'], 2, '.', ' ') }} ₽
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- ГРАФИК 1: ДОХОДЫ И РАСХОДЫ ---
        const ctx1 = document.getElementById('myChart');
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Доходы', 'Расходы'],
                datasets: [{
                    data: [{{ $income }}, {{ $expense }}],
                    backgroundColor: ['#198754', '#dc3545'],
                    hoverOffset: 4
                }]
            },
            options: {
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // --- ГРАФИК 2: КАТЕГОРИИ РАСХОДОВ ---
        // Получаем данные из PHP и превращаем их в массивы для JS
        const categoryLabels = @json(array_keys($categoryTotals));
        const categoryValues = @json(array_values($categoryTotals));

        const ctx2 = document.getElementById('categoryChart');
        new Chart(ctx2, {
            type: 'pie', // Можно 'doughnut', 'pie' или 'bar'
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryValues,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                        '#9966FF', '#FF9F40', '#C9CBCF', '#7BC225'
                    ]
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

    });
</script>
</body>
</html>