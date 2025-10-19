# 🚀 PDF Similarity Search Demo

Демо-приложение для поиска по схожести PDF-документов с использованием Laravel, PGVector и OpenAI.

## 🎯 Возможности

- 📄 Загрузка PDF-файлов и извлечение текста
- 🔍 Поиск по смысловой схожести с помощью векторного поиска
- 🤖 Генерация эмбеддингов через OpenAI API
- 🐳 Полная контейнеризация с Docker Compose
- 🎨 Современный и отзывчивый интерфейс

## 🏗️ Архитектура

- **Backend**: Laravel 12
- **Database**: PostgreSQL + PGVector
- **AI**: OpenAI API (text-embedding-3-small)
- **Frontend**: Bootstrap 5 + Blade templates
- **Containerization**: Docker + Docker Compose

## 📋 Требования

- Docker и Docker Compose
- OpenAI API ключ

## 🚀 Быстрый старт

### 1. Клонирование и настройка

```bash
git clone <repository-url>
cd pgvector-demo
```

### 2. Настройка переменных окружения

Скопируйте `.env` файл и добавьте ваш OpenAI API ключ:

```bash
cp .env.example .env
```

Отредактируйте `.env` файл и добавьте ваш API ключ:

```env
OPENAI_API_KEY=sk-your-openai-api-key-here
```

### 3. Запуск приложения

```bash
docker-compose up -d
```

### 4. Инициализация базы данных

```bash
# Генерация ключа приложения
docker-compose exec app php artisan key:generate

# Запуск миграций
docker-compose exec app php artisan migrate

# Установка зависимостей (если нужно)
docker-compose exec app composer install
```

### 5. Доступ к приложению

Откройте браузер и перейдите по адресу: http://localhost:8080

## 📖 Использование

### Загрузка документов

1. Перейдите на страницу "Upload" (http://localhost:8080/upload)
2. Выберите PDF-файл (максимум 10MB)
3. Нажмите "Upload & Process"
4. Дождитесь обработки документа

### Поиск по документам

1. На главной странице (http://localhost:8080) введите поисковый запрос
2. Нажмите "Search"
3. Просмотрите результаты с оценкой схожести

## 🔧 API Endpoints

- `GET /` - Главная страница поиска
- `POST /search` - Поиск по документам
- `GET /upload` - Страница загрузки
- `POST /upload` - Загрузка PDF-файла

## 🗄️ Структура базы данных

### Таблица `documents`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint | Первичный ключ |
| filename | varchar | Имя файла |
| content | text | Извлеченный текст |
| embedding | vector(1536) | Векторное представление текста |
| created_at | timestamp | Дата создания |
| updated_at | timestamp | Дата обновления |

## 🐳 Docker сервисы

- **app**: Laravel приложение (PHP 8.2-FPM)
- **db**: PostgreSQL с PGVector
- **nginx**: Веб-сервер

## 🔍 Как работает поиск

1. **Загрузка**: PDF обрабатывается, текст извлекается
2. **Эмбеддинг**: Текст конвертируется в вектор через OpenAI API
3. **Сохранение**: Документ и вектор сохраняются в PostgreSQL
4. **Поиск**: Запрос конвертируется в вектор и ищутся похожие документы
5. **Результаты**: Возвращаются документы с оценкой схожести

## 🛠️ Разработка

### Локальная разработка

```bash
# Установка зависимостей
composer install

# Запуск миграций
php artisan migrate

# Запуск сервера разработки
php artisan serve
```

### Логи

```bash
# Просмотр логов приложения
docker-compose logs app

# Просмотр логов базы данных
docker-compose logs db
```

## 📝 Примечания

- Убедитесь, что PDF содержит извлекаемый текст (не изображения)
- Максимальный размер файла: 10MB
- Используется модель `text-embedding-3-small` от OpenAI
- Векторы имеют размерность 1536

## 🚨 Устранение неполадок

### Проблемы с подключением к базе данных

```bash
# Проверка статуса контейнеров
docker-compose ps

# Перезапуск сервисов
docker-compose restart
```

### Проблемы с OpenAI API

- Проверьте правильность API ключа в `.env`
- Убедитесь, что у вас есть доступ к OpenAI API
- Проверьте лимиты использования API

## 📄 Лицензия

MIT License