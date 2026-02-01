<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Modern POS</title>
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --window-bg: rgba(255, 255, 255, 0.65);
            --border-color: rgba(255, 255, 255, 0.4);
            --shadow: 0 30px 60px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(0,0,0,0.05);
            --text-main: #1d1d1f;
            --text-sub: #424245;
            --accent-red: #ff3b30;
            --accent-red-hover: #ff453a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body {
            background: var(--bg-gradient);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Overlay blur for background to make the window pop-out */
        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: inherit;
            filter: blur(40px);
            z-index: -1;
            transform: scale(1.1);
        }

        /* Main Window Container */
        .mac-window {
            background: var(--window-bg);
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            width: 400px;
            border-radius: 18px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            position: relative;
            animation: windowAppear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* Header / Title Bar */
        .window-header {
            height: 44px;
            display: flex;
            align-items: center;
            padding: 0 18px;
            background: rgba(255, 255, 255, 0.1);
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
        }

        .traffic-lights {
            display: flex;
            gap: 8px;
        }

        .light {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            position: relative;
            transition: all 0.2s;
        }

        /* Small dot effect on hover traffic lights */
        .light::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 6px;
            height: 1px;
            background: rgba(0,0,0,0.3);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .traffic-lights:hover .light::after {
            opacity: 1;
        }

        .close { background: #ff5f56; border: 0.5px solid rgba(0,0,0,0.1); }
        .minimize { background: #ffbd2e; border: 0.5px solid rgba(0,0,0,0.1); }
        .maximize { background: #27c93f; border: 0.5px solid rgba(0,0,0,0.1); }

        /* Content */
        .window-content {
            padding: 32px 40px 40px;
            text-align: center;
        }

        .icon-container {
            width: 72px;
            height: 72px;
            background: linear-gradient(180deg, #ffffff 0%, #f2f2f7 100%);
            border-radius: 16px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            position: relative;
        }

        .icon-container::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .icon-container svg {
            width: 38px;
            height: 38px;
            fill: var(--accent-red);
            filter: drop-shadow(0 2px 4px rgba(255, 59, 48, 0.2));
        }

        h1 {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--text-main);
            margin-bottom: 12px;
        }

        p {
            font-size: 14px;
            color: var(--text-sub);
            line-height: 1.6;
            margin-bottom: 32px;
            padding: 0 10px;
        }

        /* Logout Button */
        .logout-form {
            width: 100%;
        }

        .btn-logout {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: var(--accent-red);
            color: white;
            box-shadow: 0 4px 12px rgba(255, 59, 48, 0.3);
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-logout:hover {
            background: var(--accent-red-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 59, 48, 0.4);
        }

        .btn-logout:active {
            transform: translateY(0) scale(0.97);
            filter: brightness(0.9);
        }

        /* Animation */
        @keyframes windowAppear {
            0% {
                opacity: 0;
                transform: scale(0.8) translateY(40px);
                filter: blur(10px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
                filter: blur(0);
            }
        }

        /* Decoration blur dots */
        .decor {
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: -1;
        }
        .decor-1 { top: -100px; right: -100px; background: rgba(255, 100, 200, 0.2); }
        .decor-2 { bottom: -100px; left: -100px; background: rgba(100, 200, 255, 0.2); }

    </style>
</head>
<body>

    <!-- Background decoration elements -->
    <div class="decor decor-1"></div>
    <div class="decor decor-2"></div>

    <div class="mac-window">
        <div class="window-header">
            <div class="traffic-lights">
                <div class="light close"></div>
                <div class="light minimize"></div>
                <div class="light maximize"></div>
            </div>
        </div>

        <div class="window-content">
            <div class="icon-container">
                <!-- Modern Logout Icon -->
                <svg viewBox="0 0 24 24">
                    <path d="M16 17v-3H9v-4h7V7l5 5-5 5M14 2a2 2 0 012 2v2h-2V4H5v16h9v-2h2v2a2 2 0 01-2 2H5a2 2 0 01-2-2V4a2 2 0 012-2h9z"/>
                </svg>
            </div>

            <h1>Access Restricted</h1>
            <p>Your session requires reconfiguration or your account permissions are insufficient to proceed to the POS dashboard.</p>

            <form action="{{ route('logout') }}" method="POST" class="logout-form">
                @csrf 
                @method('POST')
                <button type="submit" class="btn-logout">
                    <span>Sign Out of System</span>
                    <svg style="width:18px;height:18px;fill:currentColor" viewBox="0 0 24 24">
                        <path d="M14.08,15.59L16.67,13H7V11H16.67L14.08,8.41L15.5,7L20.5,12L15.5,17L14.08,15.59M19,3A2,2 0 0,1 21,5V9.67L19,7.67V5H5V19H19V16.33L21,14.33V19A2,2 0 0,1 19,21H5C3.89,21 3,20.1 3,19V5C3,3.89 3.89,3 5,3H19Z" />
                    </svg>
                </button>
            </form>
        </div>
    </div>

</body>
</html>