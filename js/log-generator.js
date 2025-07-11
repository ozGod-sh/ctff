(function() {
    const startLogGenerator = () => {
        const logContainer = document.querySelector('.log-container');
        if (!logContainer) {
            return;
        }

        const maxLogLines = 250;
        const logLevels = [
            { name: 'INFO', class: 'log-level-info' },
            { name: 'WARN', class: 'log-level-warn' },
            { name: 'ERROR', class: 'log-level-error' },
            { name: 'DEBUG', class: 'log-level-debug' }
        ];
        const logSources = ['kernel', 'systemd', 'api-gateway', 'sshd', 'cron', 'docker', 'zane-scripts', 'firewalld', 'auth-service', 'nginx', 'postgres', 'gpu-driver'];
        const logMessages = [
            "User 'guest' login failed from 192.168.1.100", "Service 'nginx' restarted successfully.", "Database connection established.",
            "API endpoint /v2/users requested.", "Cron job 'daily-backup' started.", "High CPU usage detected: 95%",
            "Memory leak suspected in process 'node-worker-123'", "Security scan initiated.", "File /tmp/data.csv processed.",
            "Invalid token received for user 'zane.cipher'", "Configuration reloaded for 'firewalld'", "New device detected: /dev/sdb1",
            "Packet dropped from 8.8.8.8: potential scan", "Starting session c3 for user 'root'", "Cleaning up temporary files in /var/tmp",
            "Request timeout on /v1/heavy-computation", "Database query executed in 350ms", "User 'admin' successfully authenticated.",
            "Container 'web-app' created and started.", "Failed to resolve DNS for 'external.service.com'", "GPU temperature rising: 78°C",
            "Compiling shaders for new graphical asset..."
        ];

        const getRandomElement = (arr) => arr[Math.floor(Math.random() * arr.length)];

        const generateLogLine = () => {
            const timestamp = new Date().toLocaleTimeString('fr-FR', { hour12: false });
            const level = getRandomElement(logLevels);
            const source = getRandomElement(logSources);
            const message = getRandomElement(logMessages);
            const line = document.createElement('div');
            line.classList.add('log-line');
            line.innerHTML = `<span class="log-time">[${timestamp}]</span> <span class="${level.class}">[${level.name}]</span> <span class="log-source">[${source}]</span> ${message}`;
            return line;
        };

        const addLog = () => {
            const newLine = generateLogLine();
            logContainer.appendChild(newLine);
            while (logContainer.children.length > maxLogLines) {
                logContainer.removeChild(logContainer.firstChild);
            }
            logContainer.scrollTop = logContainer.scrollHeight;
        };

        setInterval(addLog, 450);
    };

    if (document.readyState === 'complete') {
        startLogGenerator();
    } else {
        window.addEventListener('load', startLogGenerator);
    }
})();
