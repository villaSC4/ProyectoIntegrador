# 🏥 MediSign - Panel Médico y Traductor de Señas inteligente

MediSign es una plataforma web médica diseñada para facilitar la comunicación bidireccional en tiempo real entre dermatólogos y pacientes sordomudos. El sistema integra modelos avanzados de visión artificial en el cliente (MediaPipe y TensorFlow.js) para traducir el lenguaje de señas a texto y audio de manera nativa en el navegador, eliminando la dependencia de servidores externos de Python.

## 🛠️ Tecnologías Principales
* **Backend:** Laravel 11 / PHP 8.2+
* **Admin Panel:** Filament Framework
* **Frontend:** React (Integrado nativamente vía Vite)
* **Inteligencia Artificial:** MediaPipe Hand Landmarker & TensorFlow.js (MLP Model)

---

## 🚀 Requisitos e Instalación Paso a Paso

Sigue estos comandos en orden dentro de tu terminal para clonar, configurar y levantar el entorno local:

### 1. Clonar el Proyecto e Instalar Dependencias del Backend
```bash
composer install

php artisan key:generate


2. Configurar el Archivo de Entorno (.env)
Crea un archivo llamado .env en la raíz del proyecto y copia exactamente la siguiente configuración:

APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:ijKvjQmknFT+/sSUELecgU0/qB+joq1SejWguhfU750=
APP_DEBUG=true
APP_URL=[http://127.0.0.1:8000](http://127.0.0.1:8000)
FILAMENT_FILESYSTEM_DISK=public

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=Medisign
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=public
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

4. Instalar el Ecosistema de Frontend e Inteligencia Artificial
Ejecuta los siguientes comandos para instalar React, sus dependencias y el motor matemático de la IA en la carpeta raíz:

Bash
# Instalar el núcleo de React y conectores del DOM
npm install react react-dom

# Instalar el plugin compilador de React compatible con Vite 5
npm install @vitejs/plugin-react@5 --save-dev

# Instalar los servicios geométricos de MediaPipe Tasks Vision
npm install @mediapipe/tasks-vision

# Instalar las herramientas de tensores de TensorFlow.js
npm install @tensorflow/tfjs

# Instalar el resto de dependencias de Node mapeadas en el proyecto
npm install

## ⚡ Ejecución y Puesta en Marcha del Proyecto

Para levantar la plataforma completa y activar tanto el servidor backend como el compilador de Inteligencia Artificial en tiempo real, abre **dos terminales en paralelo** en la raíz del proyecto y ejecuta los siguientes comandos:

### 1. Servidor Backend (Laravel)
Levanta el servicio local de PHP para procesar las rutas, controladores médicos y la base de datos de MediSign:
```bash
php artisan serve

2. Servidor de Frontend e IA (Vite)
Compila los componentes de React, inyecta los hooks de seguimiento de manos y fuerza la actualización limpia del entorno:

Bash
npm run dev