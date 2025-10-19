# 🚀 PDF Similarity Search Demo

Демо-приложение для поиска по схожести PDF-документов с использованием Laravel, PGVector и OpenAI.

## 🎯 Возможности

- 📄 Загрузка PDF-файлов и извлечение текста
- 🔍 Поиск по смысловой схожести с помощью векторного поиска
- 🤖 **RAG-based conversational AI** - интеллектуальные ответы на вопросы
- 🧠 Генерация эмбеддингов через OpenAI API
- 💬 Контекстные ответы с указанием источников
- 🧩 **Автоматическое разделение больших документов** на чанки
- 🔄 Обработка документов любого размера без ограничений
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

### 6. Доступ к базе данных (Adminer)

Для просмотра данных в базе данных используйте Adminer: http://localhost:8081

**Параметры подключения:**
- **Система**: PostgreSQL
- **Сервер**: db
- **Пользователь**: laravel
- **Пароль**: secret
- **База данных**: laravel

## ⚙️ Конфигурация

### Переменные окружения

```env
# OpenAI API
OPENAI_API_KEY=sk-your-api-key-here

# RAG Configuration
OPENAI_EMBEDDING_MODEL=text-embedding-3-small
OPENAI_CHAT_MODEL=gpt-3.5-turbo
OPENAI_EMBEDDING_DIM=1536
OPENAI_SIMILARITY_THRESHOLD=0.7
OPENAI_MAX_CONTEXT_DOCS=5
OPENAI_MAX_CONTEXT_LENGTH=4000
```

## 📖 Использование

### Загрузка документов

1. Перейдите на страницу "Upload" (http://localhost:8080/upload)
2. Выберите PDF-файл (максимум 10MB)
3. Нажмите "Upload & Process"
4. Дождитесь обработки документа

### Поиск по документам

1. На главной странице (http://localhost:8080) введите поисковый запрос
2. Выберите режим поиска:
   - **AI-Powered**: Интеллектуальные ответы с контекстом
   - **Similarity Search**: Традиционный поиск по схожести
3. Нажмите "Search"
4. Просмотрите результаты:
   - AI-ответ с указанием источников
   - Список похожих документов с оценкой схожести

## 🔧 API Endpoints

- `GET /` - Главная страница поиска
- `POST /search` - Поиск по документам (RAG + similarity)
- `POST /api/search` - API endpoint для AJAX запросов
- `GET /upload` - Страница загрузки
- `POST /upload` - Загрузка PDF-файла

## 🗄️ Структура базы данных

### Таблица `documents`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint | Первичный ключ |
| filename | varchar | Имя файла |
| content | text | Извлеченный текст |
| embedding | vector(1536) | Векторное представление текста (NULL для больших документов) |
| created_at | timestamp | Дата создания |
| updated_at | timestamp | Дата обновления |

### Таблица `document_chunks`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint | Первичный ключ |
| document_id | bigint | Ссылка на документ |
| chunk_index | integer | Индекс чанка в документе |
| content | text | Содержимое чанка |
| embedding | vector(1536) | Векторное представление чанка |
| token_count | integer | Количество токенов в чанке |
| created_at | timestamp | Дата создания |
| updated_at | timestamp | Дата обновления |

## 🐳 Docker сервисы

- **app**: Laravel приложение (PHP 8.2-FPM)
- **db**: PostgreSQL с PGVector
- **nginx**: Веб-сервер
- **adminer**: Веб-интерфейс для управления базой данных

## 🔍 Как работает RAG-поиск

1. **Загрузка**: PDF обрабатывается, текст извлекается
2. **Проверка размера**: 
   - Если текст < 4000 токенов → обычная обработка
   - Если текст > 4000 токенов → разделение на чанки
3. **Эмбеддинг**: 
   - Для маленьких документов: весь текст → один вектор
   - Для больших документов: каждый чанк → отдельный вектор
4. **Сохранение**: Документ и векторы сохраняются в PostgreSQL
5. **Поиск**: 
   - Запрос конвертируется в вектор
   - Ищутся похожие документы И чанки через PGVector
   - Извлекаются релевантные фрагменты
6. **RAG-ответ**:
   - Контекст + вопрос отправляются в ChatGPT
   - Генерируется интеллектуальный ответ
   - Указываются источники с оценкой релевантности

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