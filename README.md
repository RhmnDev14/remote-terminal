# Remote Terminal (Laravel)

A mobile-first web application that acts as a remote SSH terminal client. **Developed by Rahman Umardi**, this project is built with **Laravel 12**, **Livewire**, and **TailwindCSS** to provide a real-time terminal experience in your browser.

## 🚀 Features

-   **Web-based SSH Client**: Connect to any server via SSH directly from your browser.
-   **Interactive Terminal**: Full PTY support for `nano`, `vim`, `htop`, and all interactive commands.
-   **Real-time WebSocket**: Uses xterm.js with WebSocket for true terminal experience.
-   **Mobile Optimized (PWA)**: Installable on home screen with offline capabilities.
-   **Secure**: Credentials are processed server-side via Node.js SSH2 and not exposed to the client.
-   **Single Command**: Run everything with one command (`npm run start`).

## 🛠 Tech Stack

-   **Backend**: Laravel 12 (PHP 8.2+), Node.js (SSH Proxy)
-   **Frontend**: Livewire 3, TailwindCSS 4, Alpine.js, xterm.js
-   **SSH Library**: `ssh2` (Node.js)
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

## 🚀 Usage

**Single command to run everything**:

```bash
npm run start
```

This starts:

-   Laravel server at `http://localhost:8000`
-   SSH WebSocket proxy at `ws://localhost:2222`
-   Vite dev server for hot reload

Open `http://localhost:8000` in your browser.

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

## 📱 Mobile App

Build native mobile apps for Android and iOS using Capacitor:

### Prerequisites

-   **Android**: Android Studio
-   **iOS**: Xcode (macOS only)

### Build Mobile App

1. Update WebSocket URL in `resources/js/interactive-terminal.js` for production:

    ```javascript
    this.wsUrl = options.wsUrl || "wss://your-domain.com:2222";
    ```

2. Sync and open in IDE:

    ```bash
    npm run mobile:sync
    npm run mobile:android  # Opens Android Studio
    npm run mobile:ios      # Opens Xcode
    ```

3. Build APK/IPA from the respective IDE.

## 🐳 Docker

Deploy menggunakan Docker/Podman:

```bash
# Clone dan setup
git clone https://github.com/RhmnDev14/remote-terminal.git
cd remote-terminal
cp .env.example .env

# Build dan run
docker compose up -d --build
```

App tersedia di:

-   **Web**: `http://localhost:8000`
-   **SSH Proxy**: `ws://localhost:2222`

## 📄 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
