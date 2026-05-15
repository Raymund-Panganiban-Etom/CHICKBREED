
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const pwd = document.getElementById('password');
            pwd.type = pwd.type === 'password' ? 'text' : 'password';
            this.textContent = pwd.type === 'password' ? '👁️' : '🙈';
        });
