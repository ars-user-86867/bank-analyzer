<!DOCTYPE html>
<html>
<head>
    <title>Анализатор выписок</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Загрузите банковскую выписку</h2>
    @if(session('error'))
        <div class="alert alert-danger shadow-sm">
            <strong>Ошибка!</strong> {{ session('error') }}
        </div>
    @endif

    <!-- Также полезно выводить ошибки валидации Laravel (например, если файл не выбран) -->
    @if($errors->any())
        <div class="alert alert-warning shadow-sm">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form id="uploadForm" action="/upload" method="POST" enctype="multipart/form-data" class="mt-4">
        @csrf
        <div class="mb-3">
            <label class="form-label">Выберите банк:</label>
            <select name="bank" class="form-select">
                <option value="sber">Сбербанк</option>
                <option value="tbank">Т-Банк</option>
            </select>
        </div>
        <div class="mb-3">
            <input type="file" name="statement" id="statement" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" id="submitBtn">Анализировать</button>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('uploadForm');
        const fileInput = document.getElementById('statement');
        const btn = document.getElementById('submitBtn');

        // Проверка: нашли ли мы форму? (выведет в консоль браузера F12)
        console.log('Скрипт загружен, форма найдена:', !!form);

        form.addEventListener('submit', function(event) {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const fileSizeMB = file.size / 1024 / 1024;

                console.log('Размер выбранного файла:', fileSizeMB.toFixed(2), 'MB');

                if (fileSizeMB > 10) {
                    // 1. ОСТАНАВЛИВАЕМ отправку
                    event.preventDefault(); 
                    event.stopPropagation(); // На всякий случай блокируем всплытие

                    // 2. Показываем ошибку
                    alert('Ошибка: Файл слишком большой (' + fileSizeMB.toFixed(2) + ' МБ). Максимум 5 МБ.');
                    
                    // 3. Сбрасываем поле
                    fileInput.value = '';
                    return false; 
                }
            }

            // Если всё хорошо — визуально блокируем кнопку
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Обработка...';
        });
    });
    </script>
</body>
</html>