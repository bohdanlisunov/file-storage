# 📁 File Storage

A Laravel 12 + PHP 8 web application for storing PDF and DOCX files with automatic deletion after 24 hours and RabbitMQ notifications.

## Tech Stack

- **Backend:** Laravel 12, PHP 8.3
- **Database:** MySQL 8.0
- **Message Broker:** RabbitMQ 3.x
- **Frontend:** Bootstrap 5, jQuery 3
- **Infrastructure:** Docker, Docker Compose

## Features

- Async file upload (PDF, DOC, DOCX) via drag & drop or file picker
- 10MB file size limit
- CRUD page: view list, download, and delete files
- Files are automatically deleted 24 hours after upload
- On every deletion (manual or automatic) a message is published to RabbitMQ with file details and notification email

## Project Structure

    app/
    ├── Console/Commands/DeleteExpiredFiles.php  # Artisan command for auto-deletion
    ├── Http/Controllers/FileController.php      # CRUD + async upload
    ├── Models/UploadedFile.php                  # Eloquent model
    ├── Services/RabbitMQService.php             # RabbitMQ publisher
    routes/
    ├── web.php                                  # HTTP routes
    └── console.php                              # Scheduler (everyMinute)

## Running with Docker (recommended)

### Requirements

- Docker >= 24
- Docker Compose v2

### Steps

    git clone https://github.com/bohdanlisunov/file-storage.git
    cd file-storage
    cp .env.example .env
    docker compose up -d --build
    docker compose exec app php artisan key:generate
    docker compose exec app php artisan migrate

Open: http://localhost:8080
RabbitMQ Management UI: http://localhost:15672 (guest / guest)

## Running Locally (without Docker)

### Requirements

- PHP 8.1+, Composer, MySQL 8.0, RabbitMQ 3.x

### Steps

    git clone https://github.com/bohdanlisunov/file-storage.git
    cd file-storage
    composer install
    cp .env.example .env
    # Edit .env: set DB_*, RABBITMQ_*, NOTIFICATION_EMAIL
    php artisan key:generate
    php artisan migrate

    # Terminal 1 — web server
    php artisan serve

    # Terminal 2 — scheduler (checks for expired files every minute)
    php artisan schedule:work

Open: http://127.0.0.1:8000

## Environment Variables

| Variable | Default | Description |
|---|---|---|
| `DB_HOST` | `127.0.0.1` | MySQL host |
| `DB_DATABASE` | `filestorage` | Database name |
| `DB_USERNAME` | `laraveluser` | Database user |
| `DB_PASSWORD` | `secret123` | Database password |
| `RABBITMQ_HOST` | `127.0.0.1` | RabbitMQ host |
| `RABBITMQ_PORT` | `5672` | RabbitMQ AMQP port |
| `RABBITMQ_USER` | `guest` | RabbitMQ user |
| `RABBITMQ_PASSWORD` | `guest` | RabbitMQ password |
| `RABBITMQ_QUEUE` | `file_notifications` | Queue name |
| `RABBITMQ_EXCHANGE` | `file_events` | Exchange name |
| `NOTIFICATION_EMAIL` | `admin@example.com` | Recipient email for deletion notifications |
| `MAX_FILE_SIZE_KB` | `10240` | Max upload size in KB (10MB) |

## RabbitMQ Message Format

Every file deletion (manual or auto-expired) publishes the following message:

    {
      "event": "file.deleted",
      "reason": "manual | expired",
      "timestamp": "2026-03-12T20:00:00+00:00",
      "notification_to": "admin@example.com",
      "file": {
        "id": 1,
        "original_name": "document.pdf",
        "size": 122490,
        "mime_type": "application/pdf",
        "uploaded_at": "2026-03-12T20:00:00+00:00",
        "expired_at":  "2026-03-13T20:00:00+00:00"
      }
    }

A downstream consumer reads the `notification_to` field and sends the actual email.
SMTP implementation is intentionally out of scope per task requirements.

## Artisan Commands

    # Manually trigger expired file cleanup
    php artisan files:delete-expired

    # Start the scheduler (runs cleanup every minute)
    php artisan schedule:work
