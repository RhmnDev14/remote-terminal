# Remote Terminal (Laravel)

A mobile-first web application that acts as a remote SSH terminal client. **Developed by Rahman Umardi**, this project is built with **Laravel 12**, **Livewire**, and **TailwindCSS** to provide a real-time terminal experience in your browser.

## 🚀 Features

-   **Web-based SSH Client**: Connect to any server via SSH directly from your browser.
-   **Mobile Optimized (PWA)**: Installable on home screen with offline capabilities.
-   **Real-time Interaction**: Uses Livewire for seamless command execution without page reloads.
-   **Command History**: Visual history of your commands and server outputs.
-   **Secure**: Credentials are processed server-side via `phpseclib` and not exposed to the client.
-   **Firebase Integration**: Analytics and ready-to-use boilerplate for cloud features.

## 🛠 Tech Stack

-   **Backend**: Laravel 12 (PHP 8.2+)
-   **Frontend**: Livewire 3, TailwindCSS 4, Alpine.js, Firebase JS SDK
-   **SSH Library**: `phpseclib/phpseclib` v3
-   **Database**: None (Stateless/File-based sessions)

## 📋 Prerequisites

Ensure you have the following installed:

-   **PHP 8.2+** (with `mbstring`, `openssl`, `curl` extensions)
-   **Composer**
-   **Node.js & NPM**

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

    # Node Dependencies (for TailwindCSS)
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

    Configure Firebase credentials in `.env`:

    ```ini
    VITE_FIREBASE_API_KEY=your_api_key
    VITE_FIREBASE_AUTH_DOMAIN=your_project.firebaseapp.com
    VITE_FIREBASE_PROJECT_ID=your_project_id
    # ... fill other firebase keys
    ```

    > **Important**: After changing `.env`, you must run `npm run build` to update the frontend assets.

## 🚀 Usage

1.  **Start the Server**

    ```bash
    php artisan serve
    ```

    The app will be available at `http://127.0.0.1:8000`.

2.  **Connect to a Server**
    -   Open the app on your mobile or desktop browser.
    -   Enter the **Host IP**, **Username**, and **Password** in the top bar.
    -   Click **Connect**.
    -   Once connected, type commands in the input bar at the bottom (e.g., `ls -la`, `htop`).

## 📄 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
