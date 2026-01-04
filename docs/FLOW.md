# Remote Terminal - Flow Diagram

## 1. Overall System Architecture

```mermaid
flowchart TB
    subgraph Browser["🌐 Browser (Client)"]
        UI[Terminal UI]
        Alpine[Alpine.js]
        LS[(localStorage)]
    end

    subgraph Laravel["🖥️ Laravel Server"]
        LW[Livewire Component]
        Crypt[Laravel Crypt]
        SSH[phpseclib SSH2]
    end

    subgraph Remote["🔒 Remote Server"]
        SSHD[SSH Daemon]
        Shell[Bash Shell]
    end

    UI <--> Alpine
    Alpine <--> |Encrypted Token| LS
    Alpine <--> |Livewire Protocol| LW
    LW <--> Crypt
    LW <--> SSH
    SSH <--> |SSH Protocol| SSHD
    SSHD <--> Shell
```

---

## 2. Connection Flow

```mermaid
sequenceDiagram
    participant User
    participant Browser
    participant Livewire
    participant SSH as SSH Server

    User->>Browser: Enter credentials (host, user, pass)
    User->>Browser: Click "Connect"
    Browser->>Livewire: testConnection()
    Livewire->>SSH: SSH2 Login

    alt Login Success
        SSH-->>Livewire: Connected
        Livewire->>Livewire: generateSessionToken()
        Livewire-->>Browser: {success: true, token: "encrypted..."}
        Browser->>Browser: localStorage.setItem(token)
        Browser-->>User: ✅ Connected!
    else Login Failed
        SSH-->>Livewire: Auth Failed
        Livewire-->>Browser: {success: false}
        Browser-->>User: ❌ Login Failed
    end
```

---

## 3. Session Restore Flow (Page Refresh)

```mermaid
sequenceDiagram
    participant Browser
    participant LocalStorage
    participant Livewire
    participant Crypt
    participant SSH

    Browser->>LocalStorage: getItem('ssh_session_token')
    LocalStorage-->>Browser: encrypted_token

    alt Token Exists
        Browser->>Livewire: restoreFromToken(token)
        Livewire->>Crypt: decryptString(token)
        Crypt-->>Livewire: {host, user, pass, exp}

        alt Token Valid & Not Expired
            Livewire->>SSH: SSH2 Login
            SSH-->>Livewire: Connected
            Livewire->>Crypt: Generate new token
            Livewire-->>Browser: {success: true, newToken}
            Browser->>LocalStorage: setItem(newToken)
            Browser-->>Browser: ✅ Session Restored
        else Token Expired
            Livewire-->>Browser: {success: false, message: "expired"}
            Browser->>LocalStorage: removeItem()
            Browser-->>Browser: Show login form
        end
    else No Token
        Browser-->>Browser: Show login form
    end
```

---

## 4. Command Execution Flow

```mermaid
flowchart TD
    A[User types command] --> B{Command type?}

    B -->|cd directory| C[Parse target directory]
    C --> D[Build: cd current && cd target && pwd]
    D --> E[Execute via SSH]
    E --> F{Success?}
    F -->|Yes| G[Update currentDirectory]
    F -->|No| H[Show error message]

    B -->|pwd| I[Build: cd current && pwd]
    I --> J[Execute via SSH]
    J --> K[Display path]

    B -->|Other commands| L[Build: cd current && command]
    L --> M[Execute via SSH]
    M --> N[Display output]

    G --> O[Add to history]
    H --> O
    K --> O
    N --> O

    O --> P[Clear input field]
    P --> Q[Update token with new directory]
```

---

## 5. Tab Completion Flow

```mermaid
flowchart TD
    A[User presses TAB] --> B{Connected?}
    B -->|No| Z[Do nothing]
    B -->|Yes| C[Get current input]

    C --> D[Split into parts]
    D --> E{First word?}

    E -->|Yes| F[Complete command name]
    F --> G[compgen -c prefix]

    E -->|No| H[Complete file/folder]
    H --> I[ls + grep prefix]

    G --> J{Matches found?}
    I --> J

    J -->|0 matches| Z
    J -->|1 match| K[Auto-complete input]
    J -->|Multiple| L[Show suggestions dropdown]

    L --> M{User clicks suggestion?}
    M -->|Yes| N[Insert selected item]
    M -->|No| O[Continue typing]

    K --> P[Add suffix]
    P -->|Command| Q[Add space]
    P -->|Directory| R[Add /]
    P -->|File| S[No suffix]
```

---

## 6. Encrypted Token Structure

```mermaid
flowchart LR
    subgraph Payload["📦 Payload (JSON)"]
        H[host]
        U[username]
        P[password]
        D[currentDirectory]
        E[exp - expiry time]
        I[iat - issued at]
    end

    subgraph Encryption["🔐 Encryption"]
        J[JSON.stringify]
        C[Laravel Crypt]
        A[AES-256-CBC]
    end

    subgraph Token["🎫 Final Token"]
        T[eyJpdiI6Ik...encrypted string...]
    end

    Payload --> J --> C --> A --> Token
```

---

## 7. Component State Diagram

```mermaid
stateDiagram-v2
    [*] --> Disconnected: Page Load

    Disconnected --> Connecting: Click Connect
    Disconnected --> Restoring: Token found in localStorage

    Restoring --> Connected: Token valid
    Restoring --> Disconnected: Token expired/invalid

    Connecting --> Connected: SSH Login Success
    Connecting --> Disconnected: SSH Login Failed

    Connected --> Executing: Run command
    Executing --> Connected: Command complete

    Connected --> TabComplete: Press TAB
    TabComplete --> Connected: Completion done

    Connected --> Disconnected: Click Disconnect

    note right of Connected
        - Can execute commands
        - Tab completion enabled
        - Directory tracked
        - Token auto-refreshed
    end note
```

---

## 8. Data Flow Summary

```mermaid
flowchart TB
    subgraph Client["Client Side"]
        INPUT[User Input]
        DISPLAY[Terminal Display]
        STORAGE[localStorage]
    end

    subgraph Server["Server Side"]
        LIVEWIRE[Livewire Terminal]
        ENCRYPT[Encryption Layer]
    end

    subgraph Remote["Remote Server"]
        SHELL[SSH Shell]
    end

    INPUT -->|Commands| LIVEWIRE
    INPUT -->|Credentials| LIVEWIRE

    LIVEWIRE -->|Encrypt| ENCRYPT
    ENCRYPT -->|Token| STORAGE
    STORAGE -->|Token| ENCRYPT
    ENCRYPT -->|Decrypt| LIVEWIRE

    LIVEWIRE -->|SSH exec| SHELL
    SHELL -->|Output| LIVEWIRE
    LIVEWIRE -->|Results| DISPLAY

    style ENCRYPT fill:#f9f,stroke:#333,stroke-width:2px
    style STORAGE fill:#bbf,stroke:#333,stroke-width:2px
```

---

## Legend

| Symbol | Meaning           |
| ------ | ----------------- |
| 🌐     | Browser/Client    |
| 🖥️     | Laravel Server    |
| 🔒     | Remote SSH Server |
| 📦     | Data Payload      |
| 🔐     | Encryption        |
| 🎫     | Token             |
| ✅     | Success           |
| ❌     | Failure           |
