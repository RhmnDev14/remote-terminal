# Remote Terminal (Laravel)

A mobile-first web application that acts as a remote SSH terminal client. **Developed by Rahman Umardi**, this project is built with **Laravel 12**, **Livewire**, and **TailwindCSS** to provide a real-time terminal experience in your browser.

## 🚀 Features

-   **Web-based SSH Client**: Connect to any server via SSH directly from your browser.
-   **Interactive Terminal**: Full PTY support for `nano`, `vim`, `htop`, and all interactive commands.
-   **Real-time WebSocket**: Uses xterm.js with WebSocket for true terminal experience.
-   **Mobile Optimized (PWA)**: Installable on home screen with offline capabilities.
-   **Secure**: Credentials are processed server-side via `phpseclib` and not exposed to the client.
-   **Firebase Integration**: Analytics and ready-to-use boilerplate for cloud features.

## 🛠 Tech Stack

-   **Backend**: Laravel 12 (PHP 8.2+), Node.js (SSH Proxy)
-   **Frontend**: Livewire 3, TailwindCSS 4, Alpine.js, xterm.js
-   **SSH Library**: `ssh2` (Node.js), `phpseclib/phpseclib` v3 (PHP)
-   **WebSocket**: `ws` (Node.js)
-   **Database**: None (Stateless/File-based sessions)

## 📋 Prerequisites

Ensure you have the following installed:

-   **PHP 8.2+** (with `mbstring`, `openssl`, `curl` extensions)
-   **Node.js 18+** & **NPM**
-   **Composer**

## ⚙️ Installation

1.  **Clone the Repository**

    ```bash
    git clone https://github.com/RhmnDev14/remote-terminal.git
    cd remote-terminal
    ```

2.  **Install Dependencies**

    ```bash
    # PHP Dependencies
    composer install

    # Node Dependencies
    npm install
    npm run build
    ```

3.  **Environment Setup**
    Copy the example environment file:

    ```bash
    cp .env.example .env
    ```

    Generate application key:

    ```bash
    php artisan key:generate
    ```

    Configure Firebase credentials in `.env` (optional):

    ```ini
    VITE_FIREBASE_API_KEY=your_api_key
    VITE_FIREBASE_AUTH_DOMAIN=your_project.firebaseapp.com
    VITE_FIREBASE_PROJECT_ID=your_project_id
    ```

## 🚀 Usage

You need to run **two servers**:

**Terminal 1 - SSH Proxy Server**:

```bash
npm run ssh-proxy
```

**Terminal 2 - Laravel Server**:

```bash
npm run dev &
php artisan serve
```

The app will be available at `http://127.0.0.1:8000`.

### Connecting to a Server

1. Open the app in your browser
2. Enter **Host IP**, **Username**, and **Password**
3. Click **Connect**
4. Use the terminal as you would a normal SSH session (nano, vim, htop all work!)

## 📁 Architecture

```
Browser (xterm.js)
    ↓ WebSocket
SSH Proxy Server (Node.js + ssh2)
    ↓ SSH + PTY
Remote VM Terminal
```

## 📄 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
