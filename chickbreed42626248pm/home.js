
        // Feedback submission
        const feedbackForm = document.getElementById('feedbackForm');
        const feedbackMsg = document.getElementById('feedbackMessage');
        const feedbackStatus = document.getElementById('feedbackStatus');

        feedbackForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = feedbackMsg.value.trim();
            if (!message) return alert('Please enter a message');

            const fd = new FormData();
            fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
            fd.append('message', message);

            try {
                const res = await fetch('submit_feedback.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    feedbackStatus.textContent = '✅ Thank you! Your feedback has been sent to the admin.';
                    feedbackStatus.className = 'status success';
                    feedbackMsg.value = '';
                    setTimeout(() => {
                        feedbackStatus.style.display = 'none';
                        feedbackStatus.className = 'status';
                    }, 5000);
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (err) {
                alert('Network error: ' + err.message);
            }
        });
    